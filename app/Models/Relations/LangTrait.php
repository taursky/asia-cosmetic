<?php
namespace App\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait LangTrait
{
    public function lang(): HasOne
    {
        $related_model = __CLASS__ . "Lang";
        return $this->hasOne($related_model)->where('lang', \App::getLocale());
    }

    public function langs(): HasMany
    {
        $related_model = __CLASS__ . "Lang";
        return $this->hasMany($related_model);
    }
}
