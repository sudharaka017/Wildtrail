<?php
namespace WildTrail\Services;

final class WildlifeActivityService
{
    public function level(int $animals): array
    {
        if ($animals <= 0) return ['key'=>'none','label'=>'No recent activity','color'=>'#8b949e'];
        if ($animals <= 3) return ['key'=>'low','label'=>'Low activity','color'=>'#2e8b57'];
        if ($animals <= 7) return ['key'=>'moderate','label'=>'Moderate activity','color'=>'#e5b80b'];
        if ($animals <= 12) return ['key'=>'high','label'=>'High activity','color'=>'#f28c28'];
        return ['key'=>'very-high','label'=>'Very high activity','color'=>'#d64545'];
    }
}
