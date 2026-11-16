<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DiscussionAccessService
{
    public function __construct(
        private SchoolAccessService $schoolAccess
    ) {
    }

    /**
     * @return array<string, list<string>>
     */
    public function publishedForumOptions(User $viewer): array
    {
        if (trim((string) $viewer->sekolah) === '') {
            return [];
        }

        $chapters = Chapter::query()
            ->where('is_published', true)
            ->whereHas('guru', function ($query) use ($viewer) {
                $query->where('role', 'guru');
                $this->schoolAccess->constrainUsersToSchool($query, $viewer);
            })
            ->with(['subchapters' => fn ($query) => $query->where('is_published', true)])
            ->get();
        $options = [];

        foreach ($chapters as $chapter) {
            $chapterNumber = (string) $chapter->nomor_bab;
            $subchapters = $chapter->subchapters
                ->map(fn ($subchapter) => $chapterNumber . '.' . (string) $subchapter->nomor_subbab)
                ->values()
                ->all();
            if ($subchapters !== []) {
                $options['bab-' . $chapterNumber] = $subchapters;
            }
        }

        return $options;
    }

    public function discussionsFor(User $viewer): Builder
    {
        $options = $this->publishedForumOptions($viewer);
        $query = Discussion::query()
            ->whereHas('user', fn ($user) => $this->schoolAccess->constrainUsersToSchool($user, $viewer));

        return $this->constrainTopicPairs($query, $options);
    }

    public function ensureDiscussionAccessible(User $viewer, Discussion $discussion): void
    {
        abort_unless(
            $this->discussionsFor($viewer)->whereKey($discussion->id)->exists(),
            404
        );
    }

    public function repliesFor(User $viewer, Discussion $discussion): Builder
    {
        return DiscussionReply::query()
            ->where('discussion_id', $discussion->id)
            ->whereHas('user', fn ($user) => $this->schoolAccess->constrainUsersToSchool($user, $viewer));
    }

    public function constrainReplyAuthors(Builder $replies, User $viewer): Builder
    {
        return $replies->whereHas(
            'user',
            fn ($user) => $this->schoolAccess->constrainUsersToSchool($user, $viewer)
        );
    }

    public function ensureReplyAccessible(
        User $viewer,
        Discussion $discussion,
        int $replyId
    ): void {
        abort_unless(
            $this->repliesFor($viewer, $discussion)->whereKey($replyId)->exists(),
            404
        );
    }

    /**
     * @param array<string, list<string>> $options
     */
    private function constrainTopicPairs(Builder $query, array $options): Builder
    {
        if ($options === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $topics) use ($options) {
            foreach ($options as $chapter => $subchapters) {
                if ($subchapters === []) {
                    continue;
                }

                $topics->orWhere(function (Builder $pair) use ($chapter, $subchapters) {
                    $pair->where('chapter', $chapter)
                        ->whereIn('subchapter', $subchapters);
                });
            }
        });
    }
}
