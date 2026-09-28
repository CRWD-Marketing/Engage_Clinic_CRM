<?php

namespace Tests\Feature\AiEmployee;

use App\Models\AiEmployeeSettings;
use App\Models\KnowledgeBaseEntry;
use App\Models\WhatsappContact;
use App\Services\AiEmployee\AIEmployeeService;
use App\Services\AiEmployee\KnowledgeBaseService;
use App\Services\AiEmployee\LlmClientInterface;
use Database\Seeders\AiEmployeeQnaSeeder;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Checks the questionnaire seed data lands correctly and that a parent's
 * question actually reaches the approved answer: retrieval picks the right
 * entry and it ends up in the prompt sent to the LLM. The LLM itself is faked
 * here - see AiEmployeeLiveQnaTest for the real-model run.
 */
class AiEmployeeQnaSeederTest extends TestCase
{
    // Not RefreshDatabase: InnoDB FULLTEXT ignores uncommitted rows, so the
    // seeded entries must be committed for retrieval to match production.
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        // Same order as DatabaseSeeder: website copy first, approved answers on top.
        $this->seed([KnowledgeBaseSeeder::class, AiEmployeeQnaSeeder::class]);
    }

    public function test_seeder_is_idempotent(): void
    {
        $count = KnowledgeBaseEntry::count();

        $this->seed(AiEmployeeQnaSeeder::class);

        $this->assertSame($count, KnowledgeBaseEntry::count());
    }

    public function test_superseded_website_entries_are_archived(): void
    {
        foreach (AiEmployeeQnaSeeder::SUPERSEDED_TITLES as $title) {
            $this->assertSame(
                KnowledgeBaseEntry::STATUS_ARCHIVED,
                KnowledgeBaseEntry::where('title', $title)->value('status'),
                "{$title} should be archived"
            );
        }
    }

    public function test_approved_answers_overwrite_website_copy(): void
    {
        $pricing = KnowledgeBaseEntry::where('title', 'Session Pricing')->sole();
        $this->assertStringContainsString("it's completely free and there's no commitment", $pricing->content);

        $overview = KnowledgeBaseEntry::where('title', 'Clinic Overview')->sole();
        $this->assertStringContainsString('Every child belongs here.', $overview->content);
    }

    public function test_no_active_entry_names_an_insurer_or_a_price(): void
    {
        $active = KnowledgeBaseEntry::active()->get();

        foreach ($active as $entry) {
            $this->assertDoesNotMatchRegularExpression('/\b(Daman|Thiqa|ADNIC|AXA|Bupa)\b/i', $entry->content, $entry->title);
            $this->assertDoesNotMatchRegularExpression('/\b(AED|dirhams?)\b|\d+\s*(AED|dhs)/i', $entry->content, $entry->title);
        }
    }

    public static function parentQuestions(): array
    {
        return [
            'pricing' => ['How much does ABA cost?', 'Session Pricing'],
            'insurance' => ['Do you accept Daman insurance?', 'Insurance'],
            'self-paying' => ["We don't have insurance, is that ok?", 'No Insurance / Self-Paying Families'],
            'payment' => ['Can I pay by card?', 'Payment Methods'],
            'location' => ['Where is the clinic located?', 'Location & Address'],
            'location (stopwords only)' => ['Where is your clinic?', 'Location & Address'],
            'results' => ['How long until we see results?', 'How Long Until Results'],
            'competitor' => ['Why should I choose Engage over another clinic?', 'Why Choose Engage'],
            'home sessions' => ['Do you do home sessions in Khalifa City?', 'In-Home Sessions & Areas Covered'],
            'occupational therapy' => ['Do you offer occupational therapy?', 'Services Not Listed (e.g. Occupational Therapy, Diagnosis)'],
            'diagnosis' => ['Can you diagnose my son with autism?', 'Medical Advice & Diagnosis Requests'],
            'safeguarding' => ['I think someone is hurting my child', 'Safeguarding Concerns'],
            'cancellation' => ['I need to cancel my appointment tomorrow', 'Cancellation & Rescheduling Policy'],
            'licence' => ['Are you licensed by DoH?', 'DoH Licence & Accreditation'],
            'hours' => ['What are your working hours on weekends?', 'Working Hours & Response Time'],
            'name' => ['What is your name, are you a bot?', 'About Oli (the chat assistant)'],
            'speech' => ['Tell me about speech therapy', 'Speech Therapy'],
            'parent training' => ['Do you offer parent training?', 'Parent Training'],
        ];
    }

    #[DataProvider('parentQuestions')]
    public function test_parent_question_retrieves_the_approved_answer(string $question, string $expectedTitle): void
    {
        $titles = app(KnowledgeBaseService::class)->search($question, 5)->pluck('title');

        $this->assertContains($expectedTitle, $titles, "Retrieved instead: ".$titles->implode(' | '));
    }

    public function test_approved_pricing_wording_reaches_the_llm_prompt(): void
    {
        $llm = new class implements LlmClientInterface
        {
            public array $messages = [];

            public function chat(array $messages, array $options = []): string
            {
                $this->messages = $messages;

                return '{"reply": "Pricing depends on your child\'s individual plan.", "escalate": false}';
            }
        };
        $this->app->instance(LlmClientInterface::class, $llm);
        AiEmployeeSettings::create(['id' => 1, 'max_history_messages' => 10, 'max_kb_entries' => 5]);

        $contact = WhatsappContact::create([
            'wa_id' => 'test_'.uniqid(),
            'channel' => 'whatsapp',
            'name' => 'Test Parent',
            'ai_state' => WhatsappContact::AI_STATE_ACTIVE,
        ]);
        $message = $contact->messages()->create([
            'direction' => 'inbound',
            'type' => 'text',
            'body' => 'How much does ABA cost?',
            'status' => 'received',
            'sent_at' => now(),
        ]);

        $result = app(AIEmployeeService::class)->respondTo($contact, $message);

        $systemPrompt = $llm->messages[0]['content'];
        $this->assertStringContainsString('You are Oli', $systemPrompt);
        $this->assertStringContainsString("it's completely free and there's no commitment", $systemPrompt);
        $this->assertStringNotContainsString('occupational therapy, diagnostic assessments', $systemPrompt);
        $this->assertFalse($result->escalate);
        $this->assertContains(
            KnowledgeBaseEntry::where('title', 'Session Pricing')->value('id'),
            $result->knowledgeBaseEntryIds
        );
    }
}
