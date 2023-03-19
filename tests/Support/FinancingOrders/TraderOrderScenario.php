<?php

namespace Tests\Support\FinancingOrders;

use App\Enums\MurabhaStep;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionaryNode;

class TraderOrderScenario
{
    protected StepHistoriesDictionary $dictionary;

    protected array $orderedSteps;

    protected function __construct(protected TraderOrder $traderOrder)
    {
        $this->dictionary = app(StepHistoriesDictionary::class);

        $this->orderedSteps = array_keys(MurabhaStep::$stepToHistoriesDictionary);
    }

    public static function startFrom(TraderOrder $traderOrder)
    {
        return new static(
            TraderOrder::query()
                ->where('id', $traderOrder->id)
                ->withLastHistoryAction()
                ->first()
        );
    }

    public function getTraderOrder()
    {
        return $this->traderOrder;
    }

    public function moveToStep(string $nextStep)
    {
        $currentStep = $this->dictionary->getStepByHistory($this->traderOrder->last_history_action);

        if (! $currentStep instanceof StepHistoriesDictionaryNode) {
            throw new \Exception('Step doesn\'t exists');
        }

        $positionOfCurrentStep = array_search($currentStep->step, $this->orderedSteps);

        if (($positionOfCurrentStep + 1) === count($this->orderedSteps)) {
            throw new \Exception('The current step is the last one.');
        }

        $positionOfNextStep = array_search($nextStep, $this->orderedSteps);
        if ($positionOfCurrentStep >= $positionOfNextStep) {
            throw new \Exception('Cannot move to next stage because it is the same or before the current one.');
        }

        $createAt = now();
        foreach (MurabhaStep::$stepToHistoriesDictionary[$nextStep] as $history) {
            $this->traderOrder->traderHistories()->create([
                'action' => $history,
                'created_at' => $createAt,
            ]);

            $createAt = $createAt->addMinutes(1);
        }
    }

    public function moveToHistory(int $destinationHistory)
    {
        $currentStep = $this->dictionary->getStepByHistory($this->traderOrder->last_history_action);
        $destinationStep = $this->dictionary->getStepByHistory($destinationHistory);

        if (! $currentStep instanceof StepHistoriesDictionaryNode || ! $destinationStep instanceof StepHistoriesDictionaryNode) {
            throw new \Exception('Step doesn\'t exists');
        }

        $positionOfCurrentStep = array_search($currentStep->step, $this->orderedSteps);

        if (($positionOfCurrentStep + 1) === count($this->orderedSteps)) {
            throw new \Exception('The current step is the last one.');
        }

        $positionOfNextStep = array_search($destinationStep, $this->orderedSteps);
        if ($positionOfCurrentStep >= $positionOfNextStep) {
            throw new \Exception('Cannot move to next stage because it is the same or before the current one.');
        }

        $createAt = now();

        for ($i = $positionOfCurrentStep + 1; $i <= $positionOfNextStep; $i++) {
            $nextStep = $this->orderedSteps[$i];

            foreach (MurabhaStep::$stepToHistoriesDictionary[$nextStep] as $history) {
                $this->traderOrder->traderHistories()->create([
                    'action' => $history,
                    'created_at' => $createAt,
                ]);

                if ($history === $destinationHistory) {
                    break;
                }

                $createAt = $createAt->addMinutes(1);
            }
        }
    }
}
