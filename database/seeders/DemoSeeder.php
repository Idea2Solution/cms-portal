<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Followup;
use App\Models\Inquiry;
use App\Support\Crm;
use Illuminate\Database\Seeder;

/** Sample leads, inquiries and follow-ups (names end in "(Demo)"). Run: php artisan db:seed --class=DemoSeeder */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $me = 'Emma'; // created by DatabaseSeeder

        $lead = function (int $daysAgo, array $attrs) use ($me): Company {
            $c = Company::create($attrs + [
                'code' => Crm::nextCode(Company::class, 'P'),
                'type' => 'Prospect',
                'agent' => $me,
                'created_by' => $me,
                'status' => 'Not Contacted',
                'priority' => 'Warm',
                'socials' => [],
            ]);
            $c->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
            $c->remarks()->create(['kind' => 'System', 'text' => "Lead created · source: {$c->source} · temperature: {$c->priority}", 'by' => $me]);

            return $c;
        };
        $inquiry = fn (Company $c, array $attrs) => Inquiry::create($attrs + [
            'code' => Crm::nextCode(Inquiry::class, 'INQ'),
            'company_id' => $c->id,
            'date' => now()->subDays(3)->setTime(11, 0),
            'agent' => $me,
            'priority' => $c->priority,
            'status' => 'New',
            'created_by' => $me,
        ]);
        $followup = fn (Company $c, array $attrs) => Followup::create($attrs + [
            'code' => Crm::nextCode(Followup::class, 'FU'),
            'company_id' => $c->id,
            'agent' => $me,
            'status' => 'Pending',
        ]);

        $a = $lead(6, ['name' => 'Sunrise Bakery (Demo)', 'category' => 'Food & Beverage', 'country' => 'UAE', 'city' => 'Dubai', 'contact' => 'Amina Rahman', 'designation' => 'Owner', 'phone' => '+971 50 000 0101', 'whatsapp' => '+971 50 000 0101', 'email' => 'amina@sunrise-demo.example', 'website' => 'sunrise-demo.example', 'source' => 'Instagram', 'priority' => 'Hot', 'status' => 'Contacted', 'last_contacted' => now()->subDays(3), 'socials' => [['network' => 'Instagram', 'url' => 'instagram.com/sunrisebakery.demo']]]);
        $ia = $inquiry($a, ['category' => 'Digital Marketing', 'service' => 'Social Media Management', 'requirement' => 'Manage Instagram and Facebook: 12 posts a month plus stories and a monthly performance report.', 'budget_from' => 400, 'budget_to' => 600, 'status' => 'Proposal Sent', 'source' => 'Instagram']);
        $followup($a, ['inquiry_id' => $ia->id, 'due' => now()->subDay()->setTime(11, 0), 'method' => 'WhatsApp', 'purpose' => 'Chase proposal decision']);
        $a->remarks()->create(['kind' => 'Call', 'text' => 'Spoke to Amina – wants more footfall from Instagram. Budget approved for 3 months; comparing two agencies.', 'by' => $me]);

        $b = $lead(5, ['name' => 'Al Noor Dental Clinic (Demo)', 'category' => 'Healthcare', 'country' => 'UAE', 'city' => 'Sharjah', 'contact' => 'Dr. Yusuf Khan', 'phone' => '+971 50 000 0102', 'email' => 'reception@alnoor-demo.example', 'source' => 'Website', 'status' => 'Contacted', 'last_contacted' => now()->subDays(2)]);
        $ib = $inquiry($b, ['category' => 'Website', 'service' => 'Corporate Website', 'requirement' => 'New 6-page website with online appointment booking and Arabic/English versions.', 'budget_from' => 1500, 'budget_to' => 2500, 'status' => 'In Progress', 'source' => 'Website']);
        $followup($b, ['inquiry_id' => $ib->id, 'due' => today()->setTime(16, 0), 'method' => 'Video Call', 'purpose' => 'Discovery call']);
        $b->remarks()->create(['kind' => 'Email', 'text' => 'Sent the questionnaire about pages, booking system and languages.', 'by' => $me]);

        $c = $lead(4, ['name' => 'Peak Fitness Studio (Demo)', 'category' => 'Beauty & Wellness', 'country' => 'UK', 'city' => 'Manchester', 'contact' => 'Jordan Ellis', 'phone' => '+44 7000 000103', 'source' => 'Sales Prospecting', 'priority' => 'Cold']);
        $followup($c, ['due' => today()->addDay()->setTime(10, 0), 'method' => 'Phone', 'purpose' => 'Intro call']);

        $lead(1, ['name' => 'Harbor Legal Partners (Demo)', 'category' => 'Other', 'country' => 'UK', 'city' => 'Liverpool', 'contact' => 'Priya Nair', 'designation' => 'Practice Manager', 'phone' => '+44 7000 000104', 'email' => 'priya@harbor-demo.example', 'source' => 'Website', 'priority' => 'Hot']);

        $e = $lead(9, ['name' => 'Green Leaf Organics (Demo)', 'category' => 'Retail & E-commerce', 'country' => 'UK', 'city' => 'Stockport', 'contact' => 'Marcus Bell', 'phone' => '+44 7000 000105', 'email' => 'marcus@greenleaf-demo.example', 'source' => 'Referral', 'status' => 'Qualified', 'last_contacted' => now()->subDay()]);
        $ie = $inquiry($e, ['category' => 'AI / Automation', 'service' => 'WhatsApp Automation', 'requirement' => 'Automate order enquiries and delivery updates on WhatsApp for the online shop.', 'budget_from' => 1200, 'budget_to' => 2000, 'status' => 'Negotiation']);
        $followup($e, ['inquiry_id' => $ie->id, 'due' => today()->addDays(2)->setTime(15, 0), 'method' => 'Meeting', 'purpose' => 'Negotiate scope and price']);

        $f = $lead(30, ['name' => 'Skyline Interiors (Demo)', 'category' => 'Real Estate', 'country' => 'UAE', 'city' => 'Dubai', 'contact' => 'Omar Haddad', 'phone' => '+44 7000 000106', 'email' => 'omar@skyline-demo.example', 'source' => 'LinkedIn', 'priority' => 'Hot', 'status' => 'Qualified', 'last_contacted' => now()->subDays(4)]);
        $inquiry($f, ['category' => 'Website', 'service' => 'E-commerce', 'requirement' => 'E-commerce store for interior products with catalogue and checkout.', 'budget_from' => 3000, 'budget_to' => 4500, 'status' => 'Won', 'closed_at' => now()->subDays(12), 'date' => now()->subDays(28)]);
        $f->convertToCustomer();
        $f->remarks()->create(['kind' => 'Meeting', 'text' => 'Kick-off meeting held. Assets and product list shared by the client.', 'by' => $me]);
    }
}
