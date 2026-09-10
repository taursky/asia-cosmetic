<?php
namespace App\Models\Relations;

trait FindOrCreateTrait
{
    /**
     * @param $id
     * @return static
     */
    public static function findOrCreate($id): static
    {
        $obj = static::find($id);
        return $obj ?: new static;
    }

    /**
     * @param $ref
     * @return static
     */
    public static function findOrCreateByRef($ref): static
    {
        $obj = static::where('reference',$ref)->first();
        return $obj ?: new static;
    }
}
