<?php
use SilverStripe\View\Parsers\ShortcodeParser;

ShortcodeParser::get('default')->register('ZCChart',array('StuMP90\CovidCharts\ZCChartShortCodeHandler','handle_shortcode'));
