<?php

namespace App;

use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Thread extends Model
{
    use RecordsActivity;

    protected $guarded = [];

    protected $with = ['creator', 'channel'];

    protected $appends = ['isSubscribedTo'];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($thread) {
            $thread->activity()->delete();
            $thread->replies->each->delete();
        });
    }

    public function path()
    {
        return '/threads/' . $this->channel->name . '/' . $this->slug;
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addReply($reply)
    {
        $reply = $this->replies()->create($reply);

        $this->subscriptions->filter(function ($subscriber) use ($reply) {
            return $subscriber->user_id != $reply->user_id;
        })->each->notify($reply);

        return $reply;
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }

    public function subscribe($userId = null)
    {
        $this->subscriptions()->create([
            'user_id' => $userId ?: auth()->id(),
        ]);

        return $this;
    }

    public function unsubscribe($userId = null)
    {
        return $this->subscriptions()->delete([
            'user_id' => $userId ?: auth()->id(),
        ]);
    }

    public function subscriptions()
    {
        return $this->hasMany(ThreadSubscription::class);
    }

    public function getIsSubscribedToAttribute()
    {
        return $this->subscriptions()->where('user_id', auth()->id())->exists();
    }

    public function hasUpdatesFor()
    {
        $key = auth()->user()->visitedThreadCacheKey($this);

        return $this->updated_at > cache($key);
    }

    public function recordVisit()
    {
        Redis::incr($this->visitedCacheKey());

        return $this;
    }

    public function getVisitsAttribute()
    {
        return Redis::get($this->visitedCacheKey()) ?: 0;
    }

    public function visitedCacheKey()
    {
        return 'thread.' . $this->id . '.visits';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function setSlugAttribute($value)
    {
        if (static::whereSlug($slug = str_slug($value))->exists()) {
            $slug = $this->incrementSlug($slug);
        }

        $this->attributes['slug'] = $slug;
    }

    public function incrementSlug($slug)
    {
        $original = $slug;
        $count = 2;

        while (static::whereSlug($slug)->exists()) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }
}
