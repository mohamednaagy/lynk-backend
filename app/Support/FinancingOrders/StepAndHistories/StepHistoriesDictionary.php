<?php

namespace App\Support\FinancingOrders\StepAndHistories;

use App\Enums\BursaMurabhaStep;
use App\Enums\DmccMurabhaStep;

class StepHistoriesDictionary
{
    public \SplDoublyLinkedList $dictionaryNodeList;

    public function __construct($trader, $version = null)
    {
        $this->dictionaryNodeList = new \SplDoublyLinkedList();

        foreach ($this->stepHistoriesContext($trader, $version) as $step => $histories) {
            $this->dictionaryNodeList->push(new StepHistoriesDictionaryNode($step, $histories));
        }
    }

    protected function stepHistoriesContext($trader, $version = null)
    {
        return  match ($trader) {
            'dmcc' => DmccMurabhaStep::getStepsOfVersion($version),
            'bursa' => BursaMurabhaStep::getStepsOfVersion($version),
            default => throw new \InvalidArgumentException('Invalid trader')
        };
    }

    public function getPreviousStepOf($step)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->step == $step) {
                $this->dictionaryNodeList->prev();

                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getNextStepOf($step)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->step == $step) {
                $this->dictionaryNodeList->next();

                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getStepOf($step)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->step == $step) {
                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getStepByHistory($history)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if (in_array($history, $this->dictionaryNodeList->current()->histories)) {
                return $this->dictionaryNodeList->current();
            }

            $this->dictionaryNodeList->next();
        }
    }

    public function getCompletedStepByHistory($history)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($history == end($this->dictionaryNodeList->current()->histories)) {
                return $this->dictionaryNodeList->current();
            }

            $this->dictionaryNodeList->next();
        }
    }
}
