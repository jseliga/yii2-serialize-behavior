<?php
/**
 * Behavior for automatic serialization and deserialization ActiveRecord's attributes
 *
 * @link https://github.com/jseliga/yii2-serialize-behavior
 * @copyright Copyright (c) 2016 Jan Šeliga
 * @license https://opensource.org/licenses/MIT MIT
 * @package jseliga\serialize
 * @since 1.0.0
 */

namespace jseliga\serialize;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * SerializeBehavior provides automatic serialization and deserialization of specified {@link ActiveRecord}'s
 * properties. In default configuration uses {@link Json::encode} for serialization and {@link Json::decode}
 * for deserialization.
 *
 * Example of usage:
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => jseliga\serialize\SerializeBehavior::className(),
 *             'attributes' => ['attribute1', 'attribute2']
 *         ]
 *     ];
 * }
 * ```
 *
 * @author Jan Šeliga <seliga.honza@gmail.com>
 * @since 1.0.0
 */
class SerializeBehavior extends Behavior
{
    /**
     * @var ActiveRecord
     */
    public $owner;

    /**
     * @var array|string attributes to serialize/deserialize
     */
    public $attributes;

    /**
     * @var callable|string callable for serialization of attributes
     */
    public $serialize = [Json::class, 'encode'];

    /**
     * @var callable|string callable for deserialization of attributes
     */
    public $deserialize = [Json::class, 'decode'];

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if (!$this->owner instanceof ActiveRecord) {
            throw new InvalidConfigException('Owner of behavior must be instance of "' . ActiveRecord::class . '".');
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->attributes) {
            throw new InvalidConfigException('The "attributes" property must be set.');
        }

        if (is_string($this->attributes)) {
            $this->attributes = array_map('trim', explode(',', $this->attributes));
        }

        if (!is_array($this->attributes)) {
            throw new InvalidConfigException('The "attributes" property must be string or array.');
        }

        if (!is_callable($this->serialize) || !is_callable($this->deserialize)) {
            throw new InvalidConfigException('The "serialize" and "deserialize" properties must be callable.');
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'deserializeAttributes',
            ActiveRecord::EVENT_AFTER_INSERT => 'deserializeAttributes',
            ActiveRecord::EVENT_AFTER_UPDATE => 'deserializeAttributes',
            ActiveRecord::EVENT_BEFORE_INSERT => 'serializeAttributes',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'serializeAttributes'
        ];
    }

    /**
     * Deserializes specified attributes.
     */
    public function deserializeAttributes()
    {
        $this->processModelAttributes($this->owner, $this->attributes, $this->deserialize);
    }

    /**
     * Serializes specified attributes.
     */
    public function serializeAttributes()
    {
        $this->processModelAttributes($this->owner, $this->attributes, $this->serialize);
    }

    /**
     * Processes model attributes with specified callback.
     * @param ActiveRecord $model
     * @param array $attributes
     * @param callable $callback
     */
    private function processModelAttributes($model, $attributes, $callback)
    {
        foreach ($attributes as $attribute) {
            $value = $model->getAttribute($attribute);
            if (isset($value)) {
                $model->setAttribute($attribute, call_user_func($callback, $value));
            }
        }
    }
}
