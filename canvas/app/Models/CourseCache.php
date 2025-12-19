<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCache extends Model
{
    use HasFactory;
    protected $table = 'course_cache';
    protected $primaryKey = 'canvas_course_id';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'canvas_course_id',
        'course_name',
        'course_code',
        'last_synced_at',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
     protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    /**
     * Course có nhiều assignment cache
     */
    public function assignments()
    {
        return $this->hasMany(
            AssignmentCache::class,
            'canvas_course_id',
            'canvas_course_id'
        );
    }
}
