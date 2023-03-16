<?php

namespace App\Support\FinancingOrders\StepAndHistories;

use App\Enums\MurabhaStep;

class StepHistoriesDictionary
{
    public \SplDoublyLinkedList $dictionaryNodeList;

    public function __construct()
    {
        $this->dictionaryNodeList = new \SplDoublyLinkedList();

        foreach (MurabhaStep::$stepToHistoriesDictionary as $step => $histories) {
            $this->dictionaryNodeList->push(new StepHistoriesDictionaryNode($step, $histories));
        }
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
}
