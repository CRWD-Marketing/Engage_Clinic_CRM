<?php

namespace App\Console\Commands;

use App\Models\WhatsappContact;
use App\Services\AiEmployee\AIEmployeeService;
use Illuminate\Console\Command;

/**
 * Quick way to test the AI Employee's knowledge base retrieval + LLM
 * connection from the terminal, without needing a real WhatsApp/Instagram/
 * Facebook message. Creates a throwaway test contact/message, runs the same
 * pipeline the webhook uses, and always cleans up afterward - nothing it
 * creates is left behind, win or fail.
 */
class TestAiEmployeeCommand extends Command
{
    protected $signature = 'ai-employee:test {message : The customer message to test}';

    protected $description = 'Send a test message through the AI Employee pipeline (knowledge base + LLM) to check the setup is working';

    public function handle(AIEmployeeService $service): int
    {
        $this->line('LLM endpoint: '.config('ai_employee.llm.base_url'));
        $this->line('Model: '.config('ai_employee.llm.model'));
        $this->newLine();

        $contact = WhatsappContact::create([
            'wa_id' => 'console_test_'.uniqid(),
            'channel' => 'whatsapp',
            'name' => 'Console Test',
            'ai_state' => WhatsappContact::AI_STATE_ACTIVE,
        ]);

        $message = $contact->messages()->create([
            'direction' => 'inbound',
            'type' => 'text',
            'body' => $this->argument('message'),
            'status' => 'received',
            'sent_at' => now(),
        ]);

        try {
            $this->line('Calling the LLM...');
            $result = $service->respondTo($contact, $message);

            $this->newLine();
            $this->info('Success.');
            $this->line('Reply: '.$result->reply);
            $this->line('Escalate: '.($result->escalate ? 'yes' : 'no'));
            $this->line('Knowledge base entries used: '.($result->knowledgeBaseEntryIds ? implode(', ', $result->knowledgeBaseEntryIds) : '(none matched)'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            $message->delete();
            $contact->delete();
        }
    }
}
