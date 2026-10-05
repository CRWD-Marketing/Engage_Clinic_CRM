<?php

namespace Tests\Feature\Api;

use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Storage;

class CareersTest extends ApiTestCase
{
    private function application(array $overrides = []): JobApplication
    {
        $posting = JobPosting::create(['title' => 'RBT', 'employment_type' => 'Full-time', 'location' => 'Abu Dhabi', 'status' => 'active', 'requirements' => ['RBT certification']]);

        return JobApplication::create($overrides + [
            'job_posting_id' => $posting->id, 'job_title' => 'RBT', 'first_name' => 'Sana', 'last_name' => 'Iqbal', 'email' => 'sana@example.com',
            'years_experience' => '3', 'cover_letter' => 'Keen to join.',
        ]);
    }

    public function test_the_list_filters_by_status_and_counts_each(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');
        $a = $this->application();
        $this->application(['email' => 'b@example.com', 'status' => 'hired']);

        $this->getJson($this->api('careers/applications'))->assertOk()
            ->assertJsonStructure([
                'applications' => [['id', 'job_posting_id', 'job_title', 'first_name', 'last_name', 'full_name', 'email', 'years_experience', 'cover_letter', 'status', 'status_label', 'has_resume', 'resume_name', 'created_at', 'notes']],
                'statuses' => ['new', 'reviewed', 'interviewing', 'hired', 'rejected'],
                'status_counts', 'total_count', 'new_count',
            ])
            ->assertJsonPath('total_count', 2)
            ->assertJsonPath('new_count', 1)
            ->assertJsonPath('status_counts.hired', 1);
        $this->getJson($this->api('careers/applications').'?status=new')->assertOk()->assertJsonCount(1, 'applications')->assertJsonPath('applications.0.id', $a->id);
        $this->getJson($this->api('careers/applications/count'))->assertOk()->assertJsonPath('count', 1);
    }

    public function test_status_notes_resume_and_delete(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');
        Storage::fake('local');
        Storage::disk('local')->put('resumes/cv.pdf', '%PDF-1.4 test');
        $a = $this->application(['resume_path' => 'resumes/cv.pdf', 'resume_original_name' => 'Sana CV.pdf']);

        $this->patchJson($this->api("careers/applications/{$a->id}/status"), ['status' => 'hiring'])->assertStatus(422);
        $this->patchJson($this->api("careers/applications/{$a->id}/status"), ['status' => 'interviewing'])->assertOk()
            ->assertJsonPath('message', 'Status set to Interviewing.')
            ->assertJsonPath('application.status', 'interviewing');

        $this->postJson($this->api("careers/applications/{$a->id}/notes"), ['body' => ''])->assertStatus(422);
        $this->postJson($this->api("careers/applications/{$a->id}/notes"), ['body' => 'Strong first interview'])->assertCreated()
            ->assertJsonPath('application.notes.0.body', 'Strong first interview')
            ->assertJsonPath('application.notes.0.author_name', 'HR Staff');

        $file = $this->get($this->api("careers/applications/{$a->id}/resume"))->assertOk();
        $this->assertStringContainsString('Sana CV.pdf', $file->headers->get('content-disposition'));

        $this->deleteJson($this->api("careers/applications/{$a->id}"))->assertOk()->assertJsonPath('message', 'Application deleted successfully!');
        $this->assertDatabaseMissing('job_applications', ['id' => $a->id]);
        Storage::disk('local')->assertMissing('resumes/cv.pdf');
    }

    public function test_postings_are_listed_created_edited_and_deleted(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');
        $this->application();

        $this->getJson($this->api('careers/postings'))->assertOk()
            ->assertJsonStructure(['postings' => [['id', 'title', 'employment_type', 'location', 'description', 'requirements', 'status', 'applications_count', 'created_at']], 'statuses'])
            ->assertJsonPath('postings.0.applications_count', 1);

        $this->postJson($this->api('careers/postings'), ['title' => '', 'status' => 'active'])->assertStatus(422)->assertJsonValidationErrors('title');
        $created = $this->postJson($this->api('careers/postings'), [
            'title' => 'Speech Therapist', 'employment_type' => 'Part-time', 'location' => 'Dubai', 'description' => 'Join us',
            'requirements' => ['  DHA licence ', '', 'Arabic a plus'], 'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('message', 'Job posting created successfully!')
            ->assertJsonPath('posting.requirements', ['DHA licence', 'Arabic a plus']);
        $id = $created->json('posting.id');

        $this->putJson($this->api("careers/postings/{$id}"), ['title' => 'Speech Therapist', 'status' => 'inactive', 'requirements' => []])->assertOk()
            ->assertJsonPath('posting.status', 'inactive')
            ->assertJsonPath('posting.requirements', []);

        $this->deleteJson($this->api("careers/postings/{$id}"))->assertOk()->assertJsonPath('message', 'Job posting deleted successfully!');
        $this->assertDatabaseMissing('job_postings', ['id' => $id]);
    }

    public function test_roles_without_the_module_are_refused(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $this->getJson($this->api('careers/applications'))->assertForbidden();
        $this->getJson($this->api('careers/postings'))->assertForbidden();
    }
}
