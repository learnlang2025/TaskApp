<?php

// Task Model (app/Models/Task.php)
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'user_id', 'task_date', 'completed_date', 'deleted_flag', 'completed_flag', 'created_at'];
}
