<?php

namespace App\Support\CmsForms;

use App\Models\Service;

/**
 * Starting points offered by "Create form". Each is a design (merged over
 * FormDesign::DEFAULTS) plus an ordered field list; everything can be
 * changed in the designer afterwards.
 */
class FormTemplates
{
    /**
     * "Which services are you interested in?" - its options are the clinic's
     * active Services (Billing -> Services) at the moment the form is created,
     * falling back to these when none are set up. The name contains "service"
     * so Convert to Lead maps the answer to the lead's Service.
     */
    const SERVICES_FIELD = [
        'type' => 'checkbox', 'label' => 'Which services are you interested in?', 'name' => 'services_interested',
        'options_from' => 'services',
        'options' => ['ABA therapy session', 'Speech & language therapy', 'Occupational therapy', 'Initial assessment'],
        'settings' => ['option_columns' => 2],
    ];

    const TEMPLATES = [
        'blank' => [
            'name' => 'Blank form',
            'description' => 'Name, email and phone - build the rest yourself.',
            'icon' => 'fa-file',
            'design' => [],
            'fields' => [
                ['type' => 'short_text', 'label' => 'Full Name', 'name' => 'full_name', 'is_required' => true],
                ['type' => 'email', 'label' => 'Email', 'name' => 'email', 'is_required' => true, 'placeholder' => 'name@example.com'],
                ['type' => 'phone', 'label' => 'Phone', 'name' => 'phone', 'placeholder' => '+971 5X XXX XXXX'],
                self::SERVICES_FIELD,
            ],
        ],

        'service_enquiry' => [
            'name' => 'Service enquiry',
            'description' => 'One form for all your services - clients tick the services they want (from Billing → Services).',
            'icon' => 'fa-hand-holding-heart',
            'design' => [
                'header_style' => 'banner', 'title_align' => 'center', 'radius' => 10,
                'header_kicker' => 'Engage Clinic',
                'header_subtitle' => "Tell us about your child and we'll contact you within 2 working days.",
                'submit_label' => 'Send enquiry',
            ],
            'fields' => [
                ['type' => 'section', 'label' => 'Parent / Guardian', 'name' => 'section', 'settings' => ['section_style' => 'underline']],
                ['type' => 'short_text', 'label' => 'Parent / Guardian Full Name', 'name' => 'parent_name', 'is_required' => true, 'settings' => ['width' => 6]],
                ['type' => 'dropdown', 'label' => 'Relationship to the child', 'name' => 'relationship', 'options' => ['Mother', 'Father', 'Guardian', 'Other'], 'settings' => ['width' => 6]],
                ['type' => 'phone', 'label' => 'Mobile Number', 'name' => 'phone', 'is_required' => true, 'placeholder' => '+971 5X XXX XXXX', 'settings' => ['width' => 6]],
                ['type' => 'email', 'label' => 'Email Address', 'name' => 'email', 'is_required' => true, 'placeholder' => 'name@example.com', 'settings' => ['width' => 6]],

                ['type' => 'section', 'label' => 'About your child', 'name' => 'section_2', 'settings' => ['section_style' => 'underline']],
                ['type' => 'short_text', 'label' => "Child's Full Name", 'name' => 'child_name', 'is_required' => true, 'settings' => ['width' => 6]],
                ['type' => 'number', 'label' => "Child's age", 'name' => 'child_age', 'placeholder' => 'Years', 'is_required' => true, 'settings' => ['width' => 6]],
                ['type' => 'dropdown', 'label' => 'Diagnosis', 'name' => 'diagnosis', 'placeholder' => 'Select…', 'options' => ['Autism (ASD)', 'ADHD', 'Speech / language delay', 'Global developmental delay', 'Down syndrome', 'Not diagnosed yet', 'Prefer not to say']],

                ['type' => 'section', 'label' => 'Services', 'name' => 'section_3', 'settings' => ['section_style' => 'underline']],
                ['label' => 'Which services would you like?', 'is_required' => true, 'help_text' => 'Tick all that apply.'] + self::SERVICES_FIELD,
                ['type' => 'long_text', 'label' => 'What would you like help with?', 'name' => 'message', 'placeholder' => 'e.g. speech, behaviour, school readiness', 'settings' => ['rows' => 3]],
                ['type' => 'radio', 'label' => 'Best way to reach you', 'name' => 'contact_method', 'options' => ['Phone call', 'WhatsApp', 'Email'], 'settings' => ['width' => 6, 'option_columns' => 3]],
                ['type' => 'radio', 'label' => 'Preferred time', 'name' => 'preferred_time', 'options' => ['Morning', 'Afternoon', 'Evening'], 'settings' => ['width' => 6, 'option_columns' => 3]],
                ['type' => 'file', 'label' => 'Previous reports (optional)', 'name' => 'previous_reports', 'help_text' => 'Any earlier assessment, doctor or school report.'],
                ['type' => 'consent', 'label' => 'I agree to be contacted by Engage Clinic about this enquiry', 'name' => 'consent', 'is_required' => true],
            ],
        ],

        'event' => [
            'name' => 'Event registration',
            'description' => 'Family details, sessions, time slot, support needs and photo consent, in a banner layout.',
            'icon' => 'fa-calendar-check',
            'design' => [
                'theme' => 'custom', 'header_style' => 'banner', 'title_align' => 'center', 'submit_label' => 'Register', 'radius' => 12,
                'header_kicker' => "You're invited", 'header_subtitle' => 'Date · Time · Venue',
            ],
            'fields' => [
                ['type' => 'paragraph', 'label' => 'Text block', 'name' => 'paragraph', 'settings' => ['content' => "Join us for a morning of talks, play and support for families.\nChildren of all abilities are welcome - tell us below how we can make the day comfortable for your child.", 'align' => 'center', 'label_bold' => false]],

                ['type' => 'section', 'label' => 'Your details', 'name' => 'section', 'settings' => ['section_style' => 'underline']],
                ['type' => 'short_text', 'label' => 'Full Name', 'name' => 'full_name', 'is_required' => true, 'settings' => ['width' => 6]],
                ['type' => 'dropdown', 'label' => 'Relationship to the child', 'name' => 'relationship', 'options' => ['Mother', 'Father', 'Guardian', 'Grandparent', 'Teacher / therapist', 'Other'], 'settings' => ['width' => 6]],
                ['type' => 'email', 'label' => 'Email', 'name' => 'email', 'is_required' => true, 'placeholder' => 'name@example.com', 'settings' => ['width' => 6]],
                ['type' => 'phone', 'label' => 'Phone', 'name' => 'phone', 'is_required' => true, 'placeholder' => '+971 5X XXX XXXX', 'settings' => ['width' => 6]],

                ['type' => 'section', 'label' => 'About your child', 'name' => 'section_2', 'settings' => ['section_style' => 'underline']],
                ['type' => 'short_text', 'label' => "Child's Name", 'name' => 'child_name', 'settings' => ['width' => 6]],
                ['type' => 'number', 'label' => "Child's age", 'name' => 'child_age', 'placeholder' => 'Years', 'settings' => ['width' => 6]],
                ['type' => 'dropdown', 'label' => 'Diagnosis (optional)', 'name' => 'diagnosis', 'placeholder' => 'Select…', 'options' => ['Autism (ASD)', 'ADHD', 'Speech / language delay', 'Global developmental delay', 'Down syndrome', 'Not diagnosed yet', 'Prefer not to say'], 'help_text' => 'Helps us plan the right activities - you can skip it.', 'settings' => ['width' => 12]],
                ['type' => 'number', 'label' => 'Number of adults attending', 'name' => 'attendees', 'is_required' => true, 'settings' => ['width' => 6]],
                ['type' => 'number', 'label' => 'Number of children attending', 'name' => 'children_attending', 'is_required' => true, 'settings' => ['width' => 6]],
                ['help_text' => "Optional - we'll bring information about these to the event."] + self::SERVICES_FIELD,

                ['type' => 'section', 'label' => 'Sessions', 'name' => 'section_3', 'settings' => ['section_style' => 'underline']],
                ['type' => 'radio', 'label' => 'Preferred time slot', 'name' => 'time_slot', 'is_required' => true, 'options' => ['Morning (10:00 AM – 12:00 PM)', 'Afternoon (2:00 PM – 4:00 PM)'], 'settings' => ['option_columns' => 2]],
                ['type' => 'checkbox', 'label' => 'Which sessions will you attend?', 'name' => 'sessions', 'options' => ['Welcome talk', 'Parent workshop', 'Kids activity corner', 'Sensory play zone', 'Free developmental screening', 'Q&A with therapists'], 'settings' => ['option_columns' => 2]],

                ['type' => 'section', 'label' => 'Support on the day', 'name' => 'section_4', 'settings' => ['section_style' => 'underline']],
                ['type' => 'checkbox', 'label' => 'Would your child benefit from any of these?', 'name' => 'support_needs', 'options' => ['Quiet / sensory-friendly room', 'Ear defenders', 'Visual schedule of the day', 'Wheelchair access', 'Shadow teacher coming along', 'No extra support needed'], 'settings' => ['option_columns' => 2]],
                ['type' => 'short_text', 'label' => 'Food allergies or dietary needs', 'name' => 'dietary_needs', 'placeholder' => 'e.g. nut allergy, gluten-free - or leave empty'],
                ['type' => 'radio', 'label' => 'Photos on the day', 'name' => 'photo_consent', 'is_required' => true, 'options' => ['Happy to be photographed', 'No photos of my family please'], 'settings' => ['option_columns' => 2]],
                ['type' => 'dropdown', 'label' => 'How did you hear about us?', 'name' => 'heard_from', 'options' => ['Instagram', 'WhatsApp', 'Friend or family', 'School / nursery', 'Doctor / therapist', 'Other']],
                ['type' => 'long_text', 'label' => 'Anything else we should know?', 'name' => 'message', 'settings' => ['rows' => 3]],
                ['type' => 'consent', 'label' => 'I agree to be contacted by Engage Clinic about this event', 'name' => 'consent', 'is_required' => true],
            ],
        ],
    ];

    public static function options(): array
    {
        return collect(self::TEMPLATES)->map(fn ($t) => ['name' => $t['name'], 'description' => $t['description'], 'icon' => $t['icon']])->all();
    }

    public static function get(string $key): array
    {
        return self::TEMPLATES[$key] ?? self::TEMPLATES['blank'];
    }

    /**
     * The template's fields ready to save, with live option lists filled in.
     */
    public static function fields(string $key): array
    {
        $services = null;

        return array_map(function (array $field) use (&$services) {
            if (($field['options_from'] ?? null) === 'services') {
                $services ??= Service::where('is_active', true)->orderBy('name')->pluck('name')->all();
                if ($services) {
                    $field['options'] = $services;
                }
            }
            unset($field['options_from']);

            return $field;
        }, self::get($key)['fields']);
    }
}
