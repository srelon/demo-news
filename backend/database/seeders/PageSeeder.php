<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::create([
            'slug' => 'about',
            'title' => 'About Us',
            'content' => '<h2>About Our News Portal</h2>
<p>Welcome to our news portal — your trusted source for breaking news, in-depth analysis, and the stories that matter most. We cover everything from world politics and business to entertainment, travel, and lifestyle.</p>
<h3>Our Mission</h3>
<p>We are committed to delivering accurate, timely, and unbiased journalism. Our team of experienced reporters and editors works around the clock to bring you the latest developments from every corner of the globe.</p>
<h3>Our Team</h3>
<p>Founded in 2020, our editorial team consists of seasoned journalists, analysts, and digital media experts who share a passion for storytelling and a dedication to the truth.</p>
<h3>Contact Us</h3>
<p>Have a tip or a story to share? Reach out to us at <a href="mailto:editorial@newsportal.com">editorial@newsportal.com</a>. We value reader feedback and are always looking for ways to improve our coverage.</p>',
            'deletion_protected' => true,
        ]);

        Page::create([
            'slug' => 'contact',
            'title' => 'Contact Us',
            'content' => '<h2>Get in Touch</h2>
<p>We would love to hear from you. Whether you have a news tip, a question, or feedback about our coverage, our team is here to help.</p>
<p><strong>Email:</strong> contact@newsportal.com</p>
<p><strong>Phone:</strong> +1 (555) 123-4567</p>
<p><strong>Address:</strong> 123 News Street, Media City, NY 10001</p>',
            'deletion_protected' => true,
        ]);
    }
}
