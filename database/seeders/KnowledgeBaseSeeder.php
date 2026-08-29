<?php

namespace Database\Seeders;

use App\Models\KnowledgeBaseEntry;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Seed the AI Employee's knowledge base with the clinic's own publicly
     * stated facts (pulled from the landing pages and the billing service
     * catalog), not invented copy. Where the clinic doesn't publish something
     * (e.g. a cancellation policy), the entry says so explicitly, so the AI
     * escalates to a human instead of guessing.
     */
    public function run(): void
    {
        $entries = [
            [
                'title' => 'Clinic Overview',
                'category' => 'About',
                'priority' => 5,
                'content' => "Engage Clinic (legally Engage Behavioral Development Clinic LLC) is a pediatric behavioral therapy clinic based in Abu Dhabi, UAE, and describes itself as the UAE's pioneer ABA therapy center. Its mission is to empower children and families to thrive by delivering flexible, evidence-based therapy that promotes real-world growth and inclusion, meeting families where they are - in their homes and communities. Engage serves all of Abu Dhabi with home-based and in-community therapy.",
            ],
            [
                'title' => 'Founder & Clinical Director',
                'category' => 'About',
                'priority' => 6,
                'content' => 'Engage Clinic was founded by Hong Chun Tan, BCBA, QBA, MCBA, LBA-Dubai, who has decades of experience in behavioral science across multiple countries and previously served as Founding President of the Malaysia Association of Behaviour Analysis. Free initial consultations are typically conducted with Hong Chun Tan.',
            ],
            [
                'title' => 'ABA Intervention Therapy',
                'category' => 'Services',
                'priority' => 3,
                'content' => "Applied Behavior Analysis (ABA) is Engage Clinic's core program - the gold standard in autism and developmental therapy. Programs are individualized around each child's strengths, targeting developmental needs in close partnership with parents. Includes individualized treatment plans, regular progress assessments, evidence-based methods, and home & community delivery.",
            ],
            [
                'title' => 'Intensive Early Intervention (Ages 2-6)',
                'category' => 'Services',
                'priority' => 3,
                'content' => 'For children ages 2-6. Intensive programs help young children acquire crucial skills and hit developmental milestones, setting them up for lasting school readiness. Covers developmental milestone targeting, social-emotional learning, daily living skills, communication development, and play-based therapy.',
            ],
            [
                'title' => 'School-Age Intervention (Ages 6-18)',
                'category' => 'Services',
                'priority' => 3,
                'content' => 'For children and teens ages 6-18. Provides customized educational support addressing individual learning challenges, behavior in school settings, and the social world of older childhood. Covers school-setting behavior support, social skills coaching, collaboration with educators, academic skill building, and transition planning.',
            ],
            [
                'title' => 'Speech Therapy',
                'category' => 'Services',
                'priority' => 3,
                'content' => 'Speech-language therapy supports children in developing functional communication - from early language acquisition to articulation, social communication, and AAC (augmentative and alternative communication) for non-verbal learners. Delivered in collaboration with the ABA team.',
            ],
            [
                'title' => 'Parent Training',
                'category' => 'Services',
                'priority' => 4,
                'content' => "Parent training is woven into every treatment plan, giving families the tools, feedback, and confidence to be active participants in their child's growth. Covers behavior management strategies, home routine guidance, hands-on skill coaching, and progress feedback sessions.",
            ],
            [
                'title' => 'Behavioral Assessment (FBA)',
                'category' => 'Services',
                'priority' => 4,
                'content' => 'Engage Clinic conducts thorough Functional Behavioral Assessments (FBA) to identify the root causes of challenging behaviors, through parent interviews, staff interviews, and direct observation, resulting in a written assessment report with treatment recommendations.',
            ],
            [
                'title' => 'Session Pricing',
                'category' => 'Pricing',
                'priority' => 2,
                'content' => 'Approximate session rates (AED): ABA therapy 1:1 session, 120 minutes - AED 1,100. BCBA supervision & program update - AED 1,400. Speech therapy, individual session, 45 minutes - AED 450. Parent training session, 60 minutes - AED 600. The initial 30-minute consultation is free. Rates may change - confirm current pricing with our team before quoting a family a final number.',
            ],
            [
                'title' => 'Location & Address',
                'category' => 'Location',
                'priority' => 1,
                'content' => "Engage Clinic's office is located at Office No. 1203, ADCP Commercial Tower-C, Electra Street, Abu Dhabi, UAE. Engage also provides home-based and in-community therapy sessions across Abu Dhabi.",
            ],
            [
                'title' => 'Contact Information',
                'category' => 'Contact',
                'priority' => 1,
                'content' => 'Phone / WhatsApp: +971 50 884 6801. General enquiries email: info@engagebehavior.com. Careers email: careers@engagebehavior.com.',
            ],
            [
                'title' => 'Free Consultation & Booking Process',
                'category' => 'Consultation',
                'priority' => 1,
                'content' => "Engage Clinic offers a free, no-obligation 30-minute consultation. Booking is a 3-step process: 1) pick a date, 2) choose a time, 3) provide details (child's name, child's age, parent/guardian name, phone, email, service of interest, optional notes). The clinic confirms the booked slot within 24 hours. Consultation slots are available Sunday to Thursday only (not Friday or Saturday), between 9:00 AM and 4:30 PM Abu Dhabi time (GST, UTC+4), with no slots between 11:30 AM and 1:00 PM.",
            ],
            [
                'title' => 'Insurance',
                'category' => 'Insurance',
                'priority' => 2,
                'content' => "Engage Clinic accepts health insurance. Insurance details (e.g. Daman, Thiqa) may be collected to help verify coverage, and claims are only processed with the family's consent. Coverage is not guaranteed for every provider or plan - confirm a specific plan's coverage with our team rather than promising coverage.",
            ],
            [
                'title' => 'Age Ranges & Who We Serve',
                'category' => 'General',
                'priority' => 4,
                'content' => 'Engage Clinic works with children spanning early childhood through adolescence. The Intensive Early Intervention program serves ages 2-6, and the School-Age Intervention program serves ages 6-18. ABA therapy and speech therapy are offered across this full age range based on individual needs.',
            ],
            [
                'title' => 'Cancellation & Rescheduling Policy',
                'category' => 'Policies',
                'priority' => 2,
                'content' => 'If a family asks to cancel an appointment, reschedule an appointment, or asks about a cancellation policy, rescheduling policy, no-show fee, or late-cancellation notice period for a session or consultation: there is no published policy available to share automatically. Do not guess at fees, notice periods, or rules - connect the family with our team directly to cancel or reschedule.',
            ],
            [
                'title' => 'Data Privacy',
                'category' => 'Policies',
                'priority' => 6,
                'content' => 'Engage Clinic collects contact/booking details, insurance information, and messages sent via WhatsApp/Instagram to respond to enquiries, book sessions, and maintain family/therapy records. Data is never sold. It is shared only with Meta Platforms Inc. (to operate WhatsApp/Instagram messaging), service providers under confidentiality, insurance providers with consent, or authorities where required by UAE law. Privacy questions can be directed to info@engagebehavior.com.',
            ],
            [
                'title' => 'Careers / Job Openings',
                'category' => 'Careers',
                'priority' => 7,
                'content' => 'Engage Clinic is hiring in Abu Dhabi: Board Certified Behavior Analyst (BCBA, full-time), Registered Behavior Technician (RBT, full-time/part-time), Early Intervention Specialist (full-time), and Parent Training Coordinator (part-time, flexible). Visa sponsorship is available for eligible roles. To apply, email careers@engagebehavior.com with the subject "Application: [Job Title]".',
            ],
        ];

        foreach ($entries as $entry) {
            KnowledgeBaseEntry::updateOrCreate(
                ['title' => $entry['title']],
                [...$entry, 'status' => KnowledgeBaseEntry::STATUS_ACTIVE]
            );
        }
    }
}
