<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentCache extends Model
{
    use HasFactory;
    protected $table = 'assignment_cache';

    protected $primaryKey = 'canvas_assignment_id';

    public $incrementing = false;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'canvas_assignment_id',
        'canvas_course_id',
        'name',
        'due_at',
        'last_synced_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Assignment thuộc về course
     */
    public function course()
    {
        return $this->belongsTo(
            CourseCache::class,
            'canvas_course_id',
            'canvas_course_id'
        );
    }
}
