<?php
$cloverFile = '.phpunit.cache/clover.xml';
$outputSvg = 'doc/coverage/coverage-badge.svg';

if (!file_exists($cloverFile)) {
    die("Clover file not found: $cloverFile\n");
}

$clover = simplexml_load_file($cloverFile);
if (!isset($clover->project->metrics)) {
    die("No project-level metrics found in Clover XML\n");
}

$metrics = $clover->project->metrics;
$elements = (int) $metrics['elements'];
$covered = (int) $metrics['coveredelements'];

$coverage = $elements > 0 ? round($covered / $elements * 100) : 0;

if ($coverage >= 90) {
    $color = "#4c1";
} elseif ($coverage >= 75) {
    $color = "#dfb317";
} elseif ($coverage >= 50) {
    $color = "#fe7d37";
} else {
    $color = "#e05d44";
}

$label = "coverage";
$value = $coverage . "%";
$labelWidth = 60;
$valueWidth = 40;
$height = 20;

$totalWidth = $labelWidth + $valueWidth;
$labelX = $labelWidth / 2;
$valueX = $labelWidth + ($valueWidth / 2);


$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$totalWidth}" height="{$height}">
  <linearGradient id="s" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <mask id="m">
    <rect width="{$totalWidth}" height="{$height}" rx="3" fill="#fff"/>
  </mask>
  <g mask="url(#m)">
    <rect width="{$labelWidth}" height="{$height}" fill="#555"/>
    <rect x="{$labelWidth}" width="{$valueWidth}" height="{$height}" fill="{$color}"/>
    <rect width="{$totalWidth}" height="{$height}" fill="url(#s)"/>
  </g>
  <g fill="#fff" text-anchor="middle"
     font-family="Verdana,Geneva,DejaVu Sans,sans-serif" font-size="11">
    <text x="{$labelX}" y="14">{$label}</text>
    <text x="{$valueX}" y="14">{$value}</text>
  </g>
</svg>
SVG;



file_put_contents($outputSvg, $svg);
echo "Badge created: $outputSvg ($coverage%)\n";
