<?php

/**
 * @file
 * An entity, stripped of its Agent's asking powers.
 */

namespace TestRig\Models;

use TestRig\Services\Generate;

/**
 * @class
 * Entity.
 */
class Entity extends AbstractDBObject
{
    // Database table we save to.
    protected $table = "entity";
    // Default arguments.
    private $defaultArguments = array(
        'mean_ack_time' => 5,
        'mean_answer_time' => 5,
        'mean_routing_time' => 5,
        'mean_extra_suppliers' => 0,
        'tier' => 1,
        'probability_no_ack' => 0,
    );

    /**
     * Create and save new entity.
     *
     * @param array $arguments
     *   Any arguments to be saved alongside autogenerated name.
     */
    public function create($arguments = array())
    {
        // Create data suitable for database.
        $this->data = array(
            'name' => isset($arguments['name']) ? $arguments['name'] :
                Generate::getEntityName(),
        );

        // Turn arguments into entity properties,, based on default arguments.
        foreach ($this->defaultArguments as $argumentName => $argumentData) {
            // Permit overriding by incoming arguments.
            if (isset($arguments[$argumentName])) {
                $argumentData = $arguments[$argumentName];
            }

            // Different callbacks based on argumentName.
            switch ($argumentName) {
            case 'tier':
                $this->data[$argumentName] = $argumentData;
                break;

            // Times: randomized.
            case 'mean_ack_time':
            case 'mean_answer_time':
            case 'mean_routing_time':
                $this->data[$argumentName] = Generate::getTime($argumentData);
                break;

            // Positive integers: randomized.
            case 'mean_extra_suppliers':
                // Cut-off at four times the mean i.e. 1-9 suppliers (0-8 extra) peaks at 1 + (9-1)/4 = 3.
                $this->data[$argumentName] = Generate::getNumber($argumentData, $argumentData * 4);

            // Probabilities: 0, 1 or randomized.
            case 'probability_no_ack':
                switch ($argumentData * 1) {
                case 0:
                case 1:
                    $this->data[$argumentName] = $argumentData;
                    break;

                default:
                    $this->data[$argumentName] = Generate::getProbability($argumentData);
                }
            }
        }

        // Call parent class to now create this object in the DB.
        parent::create();
    }
}
