# Quadratic Solver Page

A module for the SilverStripe CMS which allows you to display covid tracking
information via shortcodes.

Install the module and you'll have access to a new shortcode with a selection
of types.

## Examples

\[ZCChart type='england60' width='100%' height='500px' divid='chart1' \]Chart Title\[/ZCChart\] - England last 60 days

\[ZCChart type='usa60' width='100%' height='500px' divid='chart1' \]Chart Title\[/ZCChart\] - USA last 60 days

## Parameters

Type can be 'england90', 'england60', 'england30', 'usa90', 'usa60' or 'usa30'.  
Width is the width of the container div, in CSS notation.  
Height is the height of the chart in pixels, without the 'px'.  
Divid is the id of the container div.  
