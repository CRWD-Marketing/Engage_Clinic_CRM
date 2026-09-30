<?php

namespace Tests\Feature\CmsForms;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsFormsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'FULL_ADMIN', 'department' => 'EXECUTIVE']);
    }

    /**
     * An event form built the same way the builder saves it.
     */
    private function publishedForm(array $overrides = []): Form
    {
        $form = Form::create(array_merge([
            'title' => 'Autism Awareness Day',
            'slug' => 'autism-awareness-day',
            'status' => Form::STATUS_PUBLISHED,
            'success_message' => 'See you there!',
        ], $overrides));

        $fields = [
            ['type' => 'short_text', 'label' => 'Full Name', 'name' => 'full_name', 'is_required' => true],
            ['type' => 'email', 'label' => 'Email', 'name' => 'email', 'is_required' => true],
            ['type' => 'phone', 'label' => 'Phone', 'name' => 'phone'],
            ['type' => 'short_text', 'label' => "Child's name", 'name' => 'child_name'],
            ['type' => 'dropdown', 'label' => 'Interested Service', 'name' => 'interested_service', 'is_required' => true, 'options' => ['ABA Therapy', 'Speech Therapy']],
            ['type' => 'date', 'label' => 'Preferred Date', 'name' => 'preferred_date'],
            ['type' => 'checkbox', 'label' => 'Sessions', 'name' => 'sessions', 'options' => ['Morning talk', 'Afternoon workshop']],
            ['type' => 'long_text', 'label' => 'Message', 'name' => 'message'],
            ['type' => 'consent', 'label' => 'I agree to be contacted', 'name' => 'consent', 'is_required' => true],
        ];
        foreach ($fields as $i => $f) {
            $form->fields()->create($f + ['sort_order' => $i]);
        }

        return $form->fresh('fields');
    }

    private function validAnswers(): array
    {
        return ['fields' => [
            'full_name' => 'John Doe',
            'email' => 'john@email.com',
            'phone' => '+971 50 123 4567',
            'child_name' => 'Adam',
            'interested_service' => 'ABA Therapy',
            'preferred_date' => '2026-10-05',
            'sessions' => ['Morning talk'],
            'message' => 'I would like to know more about the available therapy programs.',
            'consent' => '1',
        ]];
    }

    public function test_admin_creates_builds_and_publishes_a_form(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('cms.forms.store'), ['title' => 'Family Fun Day'])
            ->assertRedirect();
        $form = Form::firstOrFail();
        $this->assertSame('family-fun-day', $form->slug);
        $this->assertSame(Form::STATUS_DRAFT, $form->status);

        $starter = $form->fields()->get();
        $payload = [
            'title' => 'Family Fun Day',
            'slug' => 'family-fun-day',
            'success_message' => 'Thanks!',
            'auto_create_lead' => false,
            'publish' => true,
            'fields' => [
                // reordered: email first, name renamed, phone dropped, a dropdown added
                ['id' => $starter[1]->id, 'type' => 'email', 'label' => 'Email', 'name' => 'email', 'is_required' => true],
                ['id' => $starter[0]->id, 'type' => 'short_text', 'label' => 'Parent name', 'name' => 'parent_name', 'is_required' => true],
                ['id' => null, 'type' => 'dropdown', 'label' => 'Ticket', 'name' => 'ticket', 'is_required' => true, 'options' => ['Adult', 'Child', '']],
            ],
        ];

        $this->actingAs($admin)->putJson(route('cms.forms.update', $form), $payload)
            ->assertOk()
            ->assertJsonPath('form.status', 'published');

        $fields = $form->fresh()->fields;
        $this->assertSame(['email', 'parent_name', 'ticket'], $fields->pluck('name')->all());
        $this->assertSame(['Adult', 'Child'], $fields[2]->options);
        $this->assertSame($starter[0]->id, $fields[1]->id);

        $this->get('/forms/family-fun-day')->assertOk()->assertSee('Family Fun Day')->assertSee('Ticket');

        $this->actingAs($admin)->get(route('cms.forms.index'))
            ->assertOk()->assertSee('Family Fun Day')->assertSee('Published')->assertSee('CMS Forms');
        $this->actingAs($admin)->get(route('cms.forms.edit', $form))
            ->assertOk()->assertSee('Section Heading')->assertSee('parent_name');
    }

    public function test_designer_autosave_keeps_work_off_the_live_form_until_saved(): void
    {
        $admin = $this->admin();
        $form = $this->publishedForm();

        $this->actingAs($admin)->postJson(route('cms.forms.autosave', $form), [
            'title' => 'Autism Awareness Day - NEW TITLE',
            'slug' => $form->slug,
            'design' => ['colors' => ['primary' => 'red;}</style><script>x</script>'], 'header_subtitle' => 'Draft subtitle'],
            'fields' => [
                ['id' => $form->fields[0]->id, 'type' => 'short_text', 'label' => 'Your name', 'name' => 'full_name', 'is_required' => true],
                ['type' => 'dropdown', 'label' => 'Pick one', 'name' => 'pick', 'options' => []], // not publishable yet - kept anyway
                ['type' => 'radio', 'label' => 'Size', 'name' => 'size', 'options' => ['S', 'M', ['nested']]],
                ['type' => 'evil', 'label' => 'x', 'name' => 'x'],
            ],
        ])->assertOk()->assertJsonStructure(['saved_at']);

        $form->refresh();
        $this->assertSame('Autism Awareness Day', $form->title, 'the live form is untouched');
        $this->get('/forms/'.$form->slug)->assertOk()->assertDontSee('NEW TITLE')->assertDontSee('Draft subtitle');
        $this->assertCount(3, $form->autosave['fields'], 'unknown field types are dropped');
        $this->assertSame(['S', 'M'], $form->autosave['fields'][2]['options']);
        $this->assertSame('#C8355F', $form->autosave['design']['colors']['primary'], 'design is sanitised');
        $this->assertSame($admin->id, $form->autosaved_by);

        // Reopening the designer restores it.
        $this->actingAs($admin)->get(route('cms.forms.edit', $form))->assertOk()
            ->assertSee('NEW TITLE')->assertSee('Draft subtitle');

        // A real save puts it on the form and clears the snapshot.
        $this->actingAs($admin)->putJson(route('cms.forms.update', $form), [
            'title' => 'Autism Awareness Day - NEW TITLE', 'slug' => $form->slug,
            'fields' => [['id' => $form->fields[0]->id, 'type' => 'short_text', 'label' => 'Your name', 'name' => 'full_name', 'is_required' => true]],
        ])->assertOk();
        $form->refresh();
        $this->assertSame('Autism Awareness Day - NEW TITLE', $form->title);
        $this->assertNull($form->autosave);
        $this->assertNull($form->autosaved_at);

        // Discard throws a snapshot away without touching the form.
        $this->actingAs($admin)->postJson(route('cms.forms.autosave', $form), ['title' => 'Scrap this', 'fields' => []])->assertOk();
        $this->actingAs($admin)->deleteJson(route('cms.forms.autosave.discard', $form))->assertOk();
        $form->refresh();
        $this->assertNull($form->autosave);
        $this->assertSame('Autism Awareness Day - NEW TITLE', $form->title);
    }

    public function test_success_messages_are_shown_as_toasts(): void
    {
        $admin = $this->admin();
        $form = $this->publishedForm();

        $this->actingAs($admin)->delete(route('cms.forms.destroy', $form))->assertRedirect(route('cms.forms.index'));

        $this->actingAs($admin)->get(route('cms.forms.index'))->assertOk()
            ->assertSee("cfToast('\\u0022Autism Awareness Day\\u0022 deleted.')", false)
            ->assertDontSee('<div class="cf-alert"><i class="fas fa-check"></i>', false);
    }

    public function test_header_text_logo_size_and_position_are_saved_and_shown(): void
    {
        $form = $this->publishedForm();

        $this->actingAs($this->admin())->putJson(route('cms.forms.update', $form), [
            'title' => $form->title,
            'slug' => $form->slug,
            'design' => [
                'header_style' => 'banner',
                'header_kicker' => "You're invited",
                'header_subtitle' => 'Saturday 5 April <b>10 AM</b>',
                'logo_size' => 500,
                'logo_align' => 'sideways',
                'logo_plate' => false,
                'header_padding' => 2,
                'subtitle_size' => 22,
                'heading_font' => 'Comic Sans',
            ],
            'fields' => [['type' => 'short_text', 'label' => 'Name', 'name' => 'name']],
        ])->assertOk();

        $d = $form->refresh()->designSettings();
        $this->assertSame(160, $d['logo_size'], 'clamped to the maximum');
        $this->assertSame(8, $d['header_padding'], 'clamped to the minimum');
        $this->assertSame('auto', $d['logo_align'], 'unknown position falls back');
        $this->assertFalse($d['logo_plate']);

        $this->get('/forms/'.$form->slug)->assertOk()
            ->assertSee('--pf-logo-size:160px', false)
            ->assertSee('--pf-subtitle-size:22px', false)
            ->assertSee('pf-kicker', false)->assertSee('You&#039;re invited', false)
            ->assertSee('Saturday 5 April &lt;b&gt;10 AM&lt;/b&gt;', false)
            ->assertDontSee('class="pf-logo-wrap has-plate"', false)
            ->assertSee('--pf-heading-font:&#039;Baloo 2&#039;', false)
            ->assertSee('family=Nunito+Sans', false)->assertSee('family=Baloo+2', false);
        $this->assertSame('Baloo 2', $d['heading_font'], 'unknown heading font falls back to the default');

        $same = \App\Support\CmsForms\FormDesign::sanitize(['font' => 'Lato', 'heading_font' => '']);
        $this->assertSame('', $same['heading_font']);
        $this->assertStringContainsString("--pf-heading-font:'Lato'", \App\Support\CmsForms\FormDesign::cssVars($same));
        $this->assertStringNotContainsString('Baloo', \App\Support\CmsForms\FormDesign::fontUrl($same));
    }

    public function test_designer_saves_a_sanitised_theme_and_layout(): void
    {
        $admin = $this->admin();
        $form = Form::create(['title' => 'Medical Form', 'slug' => 'medical-form']);

        $this->actingAs($admin)->putJson(route('cms.forms.update', $form), [
            'title' => 'Medical Form',
            'slug' => 'medical-form',
            'publish' => true,
            'design' => [
                'theme' => 'medical',
                'colors' => ['primary' => '#1c2e4a', 'page_bg' => 'red;}</style><script>alert(1)</script>'],
                'font' => 'Comic Sans',
                'header_style' => 'split',
                'radius' => 99,
                'frame' => true,
                'footer_left' => 'www.engageclinic.ae',
                'evil' => 'x',
            ],
            'fields' => [
                ['type' => 'section', 'label' => 'Personal Information', 'name' => 'section', 'settings' => ['section_style' => 'bar']],
                ['type' => 'short_text', 'label' => "Patient's Full Name", 'name' => 'full_name', 'is_required' => true, 'settings' => ['width' => 6, 'label_position' => 'left', 'label_bold' => true]],
                ['type' => 'date', 'label' => 'Date of Birth', 'name' => 'date_of_birth', 'settings' => ['width' => 6, 'label_position' => 'sideways']],
                ['type' => 'checkbox', 'label' => 'Conditions', 'name' => 'conditions', 'options' => ['Asthma', 'Diabetes'], 'settings' => ['width' => 50, 'option_columns' => 2]],
                ['type' => 'paragraph', 'label' => 'Text block', 'name' => 'paragraph', 'settings' => ['content' => 'Please answer honestly.']],
                ['type' => 'divider', 'label' => 'Divider', 'name' => 'divider', 'is_required' => true],
            ],
        ])->assertOk()->assertJsonPath('form.design.colors.primary', '#1C2E4A');

        $form->refresh();
        $d = $form->designSettings();
        $this->assertSame('#1C2E4A', $d['colors']['primary']);
        $this->assertSame('#F6F3EE', $d['colors']['page_bg'], 'invalid colour falls back to the default');
        $this->assertSame('Nunito Sans', $d['font']);
        $this->assertSame(24, $d['radius']);
        $this->assertSame('split', $d['header_style']);
        $this->assertTrue($d['frame']);
        $this->assertArrayNotHasKey('evil', $form->design);

        $fields = $form->fields->keyBy('name');
        $this->assertSame(6, $fields['full_name']->layout()['width']);
        $this->assertSame('left', $fields['full_name']->layout()['label_position']);
        $this->assertSame('', $fields['date_of_birth']->layout()['label_position']);
        $this->assertSame(12, $fields['conditions']->layout()['width']);
        $this->assertSame(2, $fields['conditions']->layout()['option_columns']);
        $this->assertFalse($fields['divider']->is_required, 'layout blocks are never required');

        $this->get('/forms/medical-form')->assertOk()
            ->assertSee('pf-header--split', false)
            ->assertSee('--span:6', false)
            ->assertSee('is-label-left', false)
            ->assertSee('Personal Information')
            ->assertSee('Please answer honestly.')
            ->assertSee('www.engageclinic.ae')
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->actingAs($admin)->get(route('cms.forms.edit', $form))->assertOk()->assertSee('fdCanvas', false);
    }

    public function test_layout_blocks_never_collect_answers(): void
    {
        $form = $this->publishedForm();
        $form->fields()->create(['type' => 'section', 'label' => 'About you', 'name' => 'section', 'sort_order' => 0]);
        $form->fields()->create(['type' => 'paragraph', 'label' => 'Text block', 'name' => 'paragraph', 'settings' => ['content' => 'Hi'], 'sort_order' => 50]);

        $this->postJson('/forms/'.$form->slug, $this->validAnswers())->assertCreated();

        $names = FormSubmission::sole()->values()->pluck('field_name');
        $this->assertNotContains('section', $names);
        $this->assertNotContains('paragraph', $names);
        $this->assertCount(9, $names);

        // A form made only of layout blocks can't go live.
        $empty = Form::create(['title' => 'Only headings', 'slug' => 'only-headings']);
        $this->actingAs($this->admin())->putJson(route('cms.forms.update', $empty), [
            'title' => 'Only headings', 'slug' => 'only-headings', 'publish' => true,
            'fields' => [['type' => 'section', 'label' => 'Hello', 'name' => 'section']],
        ])->assertStatus(422);
        $this->assertSame(Form::STATUS_DRAFT, $empty->fresh()->status);
    }

    public function test_migration_grants_the_module_to_admin_and_sales_templates(): void
    {
        $this->seed(\Database\Seeders\RoleTemplateSeeder::class);
        $migration = require database_path('migrations/2026_09_29_000002_grant_cms_forms_module.php');

        // Simulate an install that predates the module.
        \App\Models\RoleTemplate::query()->each(fn ($t) => $t->update(['modules' => array_values(array_diff($t->modules, ['cms_forms']))]));
        $sales = User::factory()->create(['role' => 'SALES_STAFF', 'department' => 'SALES']);
        $sales->applyTemplate(\App\Models\RoleTemplate::where('key', 'sales')->first());
        $this->assertFalse($sales->fresh()->canAccessFeature('cms_forms'));

        $migration->up();

        $this->assertTrue($sales->fresh()->canAccessFeature('cms_forms'));
        $this->assertContains('cms_forms', \App\Models\RoleTemplate::where('key', 'full_admin')->first()->modules);
        $this->assertNotContains('cms_forms', \App\Models\RoleTemplate::where('key', 'finance')->first()->modules);
    }

    public function test_forms_can_start_from_a_template(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('cms.forms.store'), ['template' => 'service_enquiry'])->assertRedirect();
        $form = Form::with('fields')->sole();

        $this->assertSame('Service enquiry', $form->title);
        $this->assertSame('banner', $form->designSettings()['header_style']);
        $this->assertSame('Send enquiry', $form->designSettings()['submit_label']);
        $this->assertSame(3, $form->fields->where('type', 'section')->count());
        $this->assertSame(6, $form->fields->firstWhere('name', 'child_name')->layout()['width']);

        foreach (['school_medication', 'school_admission'] as $removed) {
            $this->actingAs($admin)->post(route('cms.forms.store'), ['template' => $removed])->assertSessionHasErrors('template');
        }

        $this->actingAs($admin)->post(route('cms.forms.store'), ['title' => 'Open Day', 'template' => 'event'])->assertRedirect();
        $event = Form::with('fields')->where('title', 'Open Day')->sole();
        $this->assertSame('banner', $event->designSettings()['header_style']);
        $this->assertSame("You're invited", $event->designSettings()['header_kicker']);
        $this->assertNotNull($event->fields->firstWhere('name', 'support_needs'));

        $this->actingAs($admin)->post(route('cms.forms.store'), ['template' => 'nope'])->assertSessionHasErrors('template');

        $this->actingAs($admin)->get(route('cms.forms.index'))->assertOk()
            ->assertSee('Needs review')->assertSee('Service enquiry')->assertSee('Open designer')->assertSee('Event registration')
            ->assertDontSee('School medication form')->assertDontSee('School admission');
    }

    public function test_service_enquiry_lets_clients_pick_from_the_active_services(): void
    {
        $admin = $this->admin();
        \App\Models\Service::create(['name' => 'Speech & language therapy', 'is_active' => true, 'default_rate' => 0]);
        \App\Models\Service::create(['name' => 'ABA therapy session', 'is_active' => true, 'default_rate' => 0]);
        \App\Models\Service::create(['name' => 'Parent training', 'is_active' => false, 'default_rate' => 0]);

        $this->actingAs($admin)->post(route('cms.forms.store'), ['template' => 'service_enquiry'])->assertRedirect();
        $form = Form::with('fields')->sole();
        $this->assertSame('Service enquiry', $form->title);
        $services = $form->fields->firstWhere('name', 'services_interested');
        $this->assertSame(['ABA therapy session', 'Speech & language therapy'], $services->options, 'only active services, from Billing -> Services');
        $this->assertTrue($services->is_required);

        // Picking a service fills the lead's Service and the parent (not the child) is the lead.
        $form->update(['status' => Form::STATUS_PUBLISHED, 'published_at' => now()]);
        $this->postJson('/forms/'.$form->slug, ['fields' => [
            'parent_name' => 'Sara Ali', 'phone' => '+971501112222', 'email' => 'sara@email.com', 'child_name' => 'Adam', 'child_age' => '5',
            'services_interested' => ['Speech & language therapy'], 'consent' => '1',
        ]])->assertCreated();
        $d = app(\App\Services\CmsForms\LeadConversionService::class)->defaults(FormSubmission::sole());
        $this->assertSame('Speech & language therapy', $d['interested_in']);
        $this->assertSame('Sara Ali', $d['parent_guardian_name']);
        $this->assertSame('Adam', $d['child_name']);
        $this->assertSame('5', $d['child_age']);
    }

    public function test_ticking_several_services_keeps_the_first_and_notes_the_rest(): void
    {
        \App\Models\Service::create(['name' => 'ABA therapy session', 'is_active' => true, 'default_rate' => 0]);
        \App\Models\Service::create(['name' => 'Occupational therapy', 'is_active' => true, 'default_rate' => 0]);
        $this->actingAs($this->admin())->post(route('cms.forms.store'), ['template' => 'blank', 'title' => 'Enquiry']);
        $form = Form::sole();
        $form->update(['status' => Form::STATUS_PUBLISHED, 'published_at' => now()]);

        $this->postJson('/forms/'.$form->slug, ['fields' => [
            'full_name' => 'Sara Ali', 'email' => 'sara@email.com',
            'services_interested' => ['Occupational therapy', 'ABA therapy session'],
        ]])->assertCreated();

        $d = app(\App\Services\CmsForms\LeadConversionService::class)->defaults(FormSubmission::sole());
        $this->assertSame('Occupational therapy', $d['interested_in']);
        $this->assertStringContainsString('Occupational therapy, ABA therapy session', $d['notes']);
    }

    public function test_builder_rejects_invalid_definitions_and_url_change_while_published(): void
    {
        $admin = $this->admin();
        $form = $this->publishedForm();

        $this->actingAs($admin)->putJson(route('cms.forms.update', $form), [
            'title' => 'X', 'slug' => 'new-url',
            'fields' => [
                ['type' => 'dropdown', 'label' => 'Pick', 'name' => 'pick', 'options' => []],
                ['type' => 'short_text', 'label' => 'Dup', 'name' => 'pick'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['slug', 'fields.0.options', 'fields.0.name']);
    }

    public function test_drafts_are_not_public_but_can_be_previewed(): void
    {
        $form = $this->publishedForm(['status' => Form::STATUS_DRAFT]);

        $this->get('/forms/'.$form->slug)->assertNotFound();
        $this->postJson('/forms/'.$form->slug, $this->validAnswers())->assertNotFound();
        $this->actingAs($this->admin())->get(route('cms.forms.preview', $form))->assertOk()->assertSee('Preview of a draft');
    }

    public function test_client_submits_without_an_account_and_no_lead_is_created(): void
    {
        $form = $this->publishedForm();

        $this->postJson('/forms/'.$form->slug, $this->validAnswers())
            ->assertCreated()
            ->assertJson(['success' => true, 'message' => 'See you there!']);

        $submission = FormSubmission::with('values')->sole();
        $this->assertSame(FormSubmission::STATUS_NEW, $submission->status);
        $this->assertNull($submission->lead_id);
        $this->assertSame(0, Lead::count());

        $values = $submission->values->keyBy('field_name');
        $this->assertSame('John Doe', $values['full_name']->value);
        $this->assertSame('["Morning talk"]', $values['sessions']->value);
        $this->assertSame('1', $values['consent']->value);
        $this->assertSame('October 5, 2026', $values['preferred_date']->displayText());
        $this->assertSame('Full Name', $values['full_name']->field_label);
    }

    public function test_submission_is_validated_against_the_form_definition(): void
    {
        $form = $this->publishedForm();
        $answers = $this->validAnswers();
        $answers['fields']['email'] = 'not-an-email';
        $answers['fields']['interested_service'] = 'Something else';
        unset($answers['fields']['consent'], $answers['fields']['full_name']);

        $this->postJson('/forms/'.$form->slug, $answers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fields.email', 'fields.interested_service', 'fields.consent', 'fields.full_name']);

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_submitted_at_never_changes_when_a_response_is_updated(): void
    {
        $form = $this->publishedForm();
        $this->travelTo(now()->subDays(3));
        $this->postJson('/forms/'.$form->slug, $this->validAnswers())->assertCreated();
        $this->travelBack();
        $submission = FormSubmission::sole();
        $original = $submission->submitted_at->toDateTimeString();

        $this->actingAs($this->admin())->patch(route('cms.forms.responses.status', $submission), ['status' => 'reviewed']);

        $this->assertSame($original, $submission->fresh()->submitted_at->toDateTimeString());
    }

    public function test_honeypot_submissions_are_silently_dropped(): void
    {
        $form = $this->publishedForm();

        $this->postJson('/forms/'.$form->slug, $this->validAnswers() + ['website' => 'http://spam.example'])->assertCreated();
        $this->assertSame(0, FormSubmission::count());
    }

    public function test_staff_see_filter_and_open_the_actual_answers(): void
    {
        $admin = $this->admin();
        $form = $this->publishedForm();
        $this->postJson('/forms/'.$form->slug, $this->validAnswers());
        $other = $this->validAnswers();
        $other['fields']['full_name'] = 'Jane Smith';
        $other['fields']['email'] = 'jane@email.com';
        $this->postJson('/forms/'.$form->slug, $other);
        $jane = FormSubmission::latest('id')->first();
        $jane->update(['status' => FormSubmission::STATUS_REVIEWED]);

        $this->actingAs($admin)->get(route('cms.forms.responses.index', $form))
            ->assertOk()->assertSee('John Doe')->assertSee('jane@email.com');

        $this->actingAs($admin)->get(route('cms.forms.responses.index', [$form, 'search' => 'Jane']))
            ->assertOk()->assertSee('Jane Smith')->assertDontSee('john@email.com');

        $this->actingAs($admin)->get(route('cms.forms.responses.index', [$form, 'status' => 'new']))
            ->assertOk()->assertSee('John Doe')->assertDontSee('jane@email.com');

        $this->actingAs($admin)->get(route('cms.forms.responses.index', [$form, 'from' => now()->addDay()->format('Y-m-d')]))
            ->assertOk()->assertDontSee('john@email.com');

        $john = FormSubmission::oldest('id')->first();
        $this->actingAs($admin)->get(route('cms.forms.responses.show', $john))
            ->assertOk()
            ->assertSee('I would like to know more about the available therapy programs.')
            ->assertSee('+971 50 123 4567')
            ->assertSee('October 5, 2026')
            ->assertSee('Agreed')
            ->assertSee('Convert to Lead');
    }

    public function test_responses_export_as_csv_with_the_current_filters(): void
    {
        $admin = $this->admin();
        $form = $this->publishedForm();
        $this->postJson('/forms/'.$form->slug, $this->validAnswers());
        $other = $this->validAnswers();
        $other['fields']['full_name'] = '=HYPERLINK("http://evil.example")';
        $other['fields']['email'] = 'jane@email.com';
        $this->postJson('/forms/'.$form->slug, $other);
        FormSubmission::latest('id')->first()->update(['status' => FormSubmission::STATUS_REVIEWED]);

        $csv = $this->actingAs($admin)->get(route('cms.forms.responses.export', $form))
            ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->streamedContent();
        $this->assertStringContainsString('"Response #",Submitted,Status,Lead', $csv);
        $this->assertStringContainsString('John Doe', $csv);
        $this->assertStringContainsString('jane@email.com', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'Answers starting with = are neutralised for Excel');

        $filtered = $this->actingAs($admin)->get(route('cms.forms.responses.export', [$form, 'status' => 'new']))->streamedContent();
        $this->assertStringContainsString('John Doe', $filtered);
        $this->assertStringNotContainsString('jane@email.com', $filtered);
    }

    public function test_convert_to_lead_uses_the_existing_lead_pipeline_once(): void
    {
        $admin = $this->admin();
        $form = $this->publishedForm();
        $this->postJson('/forms/'.$form->slug, $this->validAnswers());
        $submission = FormSubmission::sole();

        $this->actingAs($admin)->post(route('cms.forms.responses.convert', $submission), [
            'parent_guardian_name' => 'John Doe',
            'child_name' => 'Adam',
            'email' => 'john@email.com',
            'phone' => '+971501234567',
            'interested_in' => 'ABA Therapy',
            'notes' => 'Edited note',
            'source' => 'Autism Awareness Day',
        ])->assertRedirect(route('cms.forms.responses.show', $submission))
            ->assertSessionHas('converted', sprintf('LD-%06d', Lead::sole()->id));

        $lead = Lead::sole();
        $this->assertSame('John Doe', $lead->parent_guardian_name);
        $this->assertSame('Adam', $lead->child_name);
        $this->assertSame('ABA Therapy', $lead->interested_in);
        $this->assertSame('Edited note', $lead->notes);
        $this->assertSame('Autism Awareness Day', $lead->source);
        $this->assertSame('Autism Awareness Day', $lead->lead_form_name);
        $this->assertSame(Lead::STATUS_NEW, $lead->fresh()->status);
        $this->assertTrue($lead->activities()->where('type', LeadActivity::TYPE_NOTE)->where('body', 'like', "%#{$submission->id}%")->exists());

        $submission->refresh();
        $this->assertSame(FormSubmission::STATUS_CONVERTED, $submission->status);
        $this->assertSame($lead->id, $submission->lead_id);
        $this->assertSame($admin->id, $submission->converted_by);
        $this->assertNotNull($submission->converted_at);
        $this->assertSame(9, $submission->values()->count(), 'Original answers are kept');

        // Second attempt must not create another lead.
        $this->actingAs($admin)->post(route('cms.forms.responses.convert', $submission), ['parent_guardian_name' => 'John Doe', 'source' => 'X'])
            ->assertSessionHasErrors('message');
        $this->assertSame(1, Lead::count());

        $this->actingAs($admin)->get(route('cms.forms.responses.show', $submission))
            ->assertOk()->assertSee('View Lead')->assertSee(sprintf('LD-%06d', $lead->id))->assertDontSee('Convert to Lead</button>', false);

        // Converted responses can't be moved back to another status.
        $this->actingAs($admin)->patch(route('cms.forms.responses.status', $submission), ['status' => 'new'])->assertSessionHasErrors('message');
        $this->assertSame(FormSubmission::STATUS_CONVERTED, $submission->fresh()->status);
    }

    public function test_convert_modal_is_prefilled_from_the_answers(): void
    {
        $form = $this->publishedForm();
        $this->postJson('/forms/'.$form->slug, $this->validAnswers());

        $defaults = app(\App\Services\CmsForms\LeadConversionService::class)->defaults(FormSubmission::sole());

        $this->assertSame('John Doe', $defaults['parent_guardian_name']);
        $this->assertSame('Adam', $defaults['child_name']);
        $this->assertSame('john@email.com', $defaults['email']);
        $this->assertSame('+971 50 123 4567', $defaults['phone']);
        $this->assertSame('ABA Therapy', $defaults['interested_in']);
        $this->assertSame('I would like to know more about the available therapy programs.', $defaults['notes']);
        $this->assertSame('Autism Awareness Day', $defaults['source']);
    }

    public function test_auto_create_lead_only_when_configured(): void
    {
        $form = $this->publishedForm(['auto_create_lead' => true]);
        $answers = $this->validAnswers();
        $answers['fields']['phone'] = '+971501234567';

        $this->postJson('/forms/'.$form->slug, $answers)->assertCreated();

        $submission = FormSubmission::sole();
        $this->assertSame(FormSubmission::STATUS_CONVERTED, $submission->status);
        $this->assertNull($submission->converted_by);
        $this->assertSame('John Doe', Lead::sole()->parent_guardian_name);
    }

    public function test_deleting_a_form_with_responses_needs_explicit_confirmation(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $form = $this->publishedForm();
        $form->fields()->create(['type' => 'file', 'label' => 'Report', 'name' => 'report', 'sort_order' => 99]);
        $answers = $this->validAnswers();
        $answers['fields']['report'] = UploadedFile::fake()->create('report.pdf', 20, 'application/pdf');
        $this->post('/forms/'.$form->slug, $answers, ['Accept' => 'application/json'])->assertCreated();
        $this->postJson('/forms/'.$form->slug, $this->validAnswers())->assertCreated();

        $converted = FormSubmission::oldest('id')->first();
        $this->actingAs($admin)->post(route('cms.forms.responses.convert', $converted), ['parent_guardian_name' => 'John Doe', 'source' => 'Event']);
        $filePath = $converted->values()->whereNotNull('file_path')->value('file_path');
        Storage::disk('local')->assertExists($filePath);

        // Without the acknowledgement nothing is deleted.
        $this->actingAs($admin)->delete(route('cms.forms.destroy', $form))->assertSessionHasErrors('message');
        $this->actingAs($admin)->delete(route('cms.forms.destroy', $form), ['confirm_delete_responses' => '0'])->assertSessionHasErrors('message');
        $this->assertModelExists($form);
        $this->assertSame(2, FormSubmission::count());

        $this->actingAs($admin)->delete(route('cms.forms.destroy', $form), ['confirm_delete_responses' => '1'])
            ->assertRedirect(route('cms.forms.index'))
            ->assertSessionHas('success', '"Autism Awareness Day" and its 2 responses were deleted.');

        $this->assertModelMissing($form);
        $this->assertSame(0, FormSubmission::count());
        $this->assertSame(0, \App\Models\FormSubmissionValue::count());
        Storage::disk('local')->assertMissing($filePath);
        $this->assertSame(1, Lead::count(), 'Leads created from responses stay in the pipeline');

        // A form without responses only needs the plain confirmation.
        $empty = Form::create(['title' => 'Empty', 'slug' => 'empty']);
        $this->actingAs($admin)->delete(route('cms.forms.destroy', $empty))->assertRedirect(route('cms.forms.index'));
        $this->assertModelMissing($empty);
    }

    public function test_duplicate_creates_a_draft_copy(): void
    {
        $form = $this->publishedForm();

        $this->actingAs($this->admin())->post(route('cms.forms.duplicate', $form))->assertRedirect();

        $copy = Form::where('id', '!=', $form->id)->sole();
        $this->assertSame(Form::STATUS_DRAFT, $copy->status);
        $this->assertSame('autism-awareness-day-copy', $copy->slug);
        $this->assertSame($form->fields()->count(), $copy->fields()->count());
    }

    public function test_module_is_gated_by_existing_permissions(): void
    {
        $form = $this->publishedForm();
        $this->postJson('/forms/'.$form->slug, $this->validAnswers());
        $submission = FormSubmission::sole();

        $this->get(route('cms.forms.index'))->assertRedirect(route('login'));

        $therapist = User::factory()->create(['role' => 'THERAPIST', 'department' => 'CLINICAL']);
        $this->actingAs($therapist)->get(route('cms.forms.index'))->assertForbidden();

        // Coordinators reach CMS Forms via a custom grant but can't create leads - same rule as Contacts.
        $coordinator = User::factory()->create(['role' => 'COORDINATOR', 'department' => 'COORDINATOR', 'modules' => ['dashboard', 'leads', 'cms_forms']]);
        $this->actingAs($coordinator)->get(route('cms.forms.responses.show', $submission))->assertOk()->assertDontSee('Convert to Lead</button>', false);
        $this->actingAs($coordinator)->post(route('cms.forms.responses.convert', $submission), ['parent_guardian_name' => 'X', 'source' => 'Y'])->assertForbidden();
        $this->assertSame(0, Lead::count());

        // View-only access can read responses but not change them.
        $viewer = User::factory()->create(['role' => 'SALES_STAFF', 'department' => 'SALES', 'modules' => ['leads', 'cms_forms'], 'module_levels' => ['cms_forms' => 'view']]);
        $this->actingAs($viewer)->get(route('cms.forms.responses.index', $form))->assertOk();
        $this->actingAs($viewer)->patch(route('cms.forms.responses.status', $submission), ['status' => 'reviewed'])->assertForbidden();
    }

    public function test_file_uploads_are_stored_and_downloadable_by_staff_only(): void
    {
        Storage::fake('local');
        $form = $this->publishedForm();
        $form->fields()->create(['type' => 'file', 'label' => 'Report', 'name' => 'report', 'sort_order' => 99]);

        $answers = $this->validAnswers();
        $answers['fields']['report'] = UploadedFile::fake()->create('assessment.pdf', 120, 'application/pdf');
        $this->post('/forms/'.$form->slug, $answers, ['Accept' => 'application/json'])->assertCreated();

        $value = FormSubmission::sole()->values()->where('field_name', 'report')->sole();
        Storage::disk('local')->assertExists($value->file_path);
        $this->assertSame('assessment.pdf', $value->file_original_name);

        $bad = $this->validAnswers();
        $bad['fields']['report'] = UploadedFile::fake()->create('script.exe', 10);
        $this->post('/forms/'.$form->slug, $bad, ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('fields.report');

        $url = route('cms.forms.responses.file', [$value->form_submission_id, $value->id]);
        $this->get($url)->assertRedirect();
        $this->actingAs($this->admin())->get($url)->assertOk()->assertDownload('assessment.pdf');
    }

    public function test_embed_mode_and_html_fallback_submit(): void
    {
        $form = $this->publishedForm(['design' => ['frame' => true, 'footer_left' => 'www.engageclinic.ae']]);

        // Embedded, the page drops its own frame stripes and footer so it sits inside the host site.
        $this->get('/forms/'.$form->slug.'?embed=1')->assertOk()->assertDontSee('class="pf-frame', false)->assertDontSee('www.engageclinic.ae');
        $this->get('/forms/'.$form->slug)->assertOk()->assertSee('class="pf-frame', false)->assertSee('www.engageclinic.ae');
        $this->post('/forms/'.$form->slug, $this->validAnswers() + ['embed' => 1])
            ->assertRedirect(route('forms.public.show', ['slug' => $form->slug, 'submitted' => 1, 'embed' => 1]));
        $this->get('/forms/'.$form->slug.'?submitted=1')->assertSee('See you there!');
    }
}
