<?php

if (!function_exists('avatarColor')) {
    function avatarColor(string $name): string
    {
        $colors = ['#e94560','#0f3460','#16213e','#533483','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6'];
        return $colors[abs(crc32($name)) % count($colors)];
    }
}
