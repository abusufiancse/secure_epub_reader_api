<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model {
    protected $fillable = ['user_id','ebook_id','status'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function ebook(): BelongsTo { return $this->belongsTo(Ebook::class); }
}
