<?php

namespace App\Jobs;

use App\Baha\PosterData;
use App\Baha\Scraper;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchPostComments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = "https://forum.gamer.com.tw/ajax/moreCommend.php?bsn=60076&snB=";

        $scraper = new Scraper();

        $crawler = $scraper->getPage("{$url}{$this->post->no}");

        $data = json_decode($crawler->text(), true);

        if (!isset($data['next_snC'])) {
            $this->fail();
        }

        collect($data)->except('next_snC')->each(function ($data) {
            Comment::updateOrCreate([
                'post_id' => $this->post->id,
                'poster_id' => (new PosterData($data['userid'], $data['nick']))->save()->id,
                'content' => $data['content'],
                'created_at' => $data['wtime'],
            ], [
                'inserted_at' => now()->toDateTimeString(),
            ]);
        });
    }
}
