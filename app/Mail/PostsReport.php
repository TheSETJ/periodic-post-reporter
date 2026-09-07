<?php

namespace App\Mail;

use App\Exports\PostsReportExport;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class PostsReport extends Mailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $reportTitle,
        public Carbon $start,
        public Carbon $end,
        public Collection $postsHistogram,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->reportTitle,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.posts-report',
            with: [
                'title' => $this->reportTitle,
                'start' => $this->start,
                'end' => $this->end,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => Excel::raw(new PostsReportExport($this->postsHistogram), \Maatwebsite\Excel\Excel::XLSX),
                'report.xlsx'
            ),
        ];
    }
}
