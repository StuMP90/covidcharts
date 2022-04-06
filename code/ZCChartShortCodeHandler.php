<?php
namespace StuMP90\CovidCharts;

use SilverStripe\View\Parsers\ShortcodeHandler;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;

class ZCChartShortCodeHandler implements ShortcodeHandler
{
    /**
     * Gets the list of shortcodes provided by this handler
     *
     * @return mixed
     */
    public static function get_shortcodes() {
        return array('ZCChart');
    }

    /**
     * Process the shortcode
     * 
     * @param array $arguments
     * @param string $content
     * @param ShortcodeParser $parser
     * @param string $shortcode
     * @param array $extra
     *
     * @return string
     */
    public static function handle_shortcode($arguments, $content, $parser, $shortcode, $extra = array()) {
        
        // If type is blank or not in the allowed list, stop now
        $allowed_types = array("england90", "usa90","england60", "usa60","england30", "usa30");
        if ((empty($arguments['type'])) || (!(in_array($arguments['type'], $allowed_types, true)))) {
            return;
        }

        // Set caption from content 
        if (!empty($content)) {
            $arguments['Caption'] = $content;
        } else {
            $arguments['Caption'] = '';
        }

        // Check if width is set and set a default if not
        if ((isset($arguments['width'])) && (!empty($arguments['width']))) {
            $arguments['Width'] = $arguments['width'];
        } else {
            $arguments['Width'] = '100%';
        }
        
        // Check if height is set and set a default if not
        if ((isset($arguments['height'])) && (!empty($arguments['height']))) {
            $arguments['Height'] = $arguments['height'];
        } else {
            $arguments['Height'] = '100%';
        }

        // Check if divid is set and set a default if not
        if ((isset($arguments['divid'])) && (!empty($arguments['divid']))) {
            $arguments['Divid'] = $arguments['divid'];
        } else {
            $arguments['Divid'] = 'zchart';
        }
        
        if (!empty($content)) {
            $arguments['Content'] = $content;
        }

        $customchart = array();
        $customchart['ChartType'] = $arguments['type'];
        
        // Get the data
        switch ($arguments['type']) {
            case 'england90':
                $case_data = \StuMP90\CovidCharts\ZCChartShortCodeHandler::getUKData("england",90);
                break;
            case 'england60':
                $case_data = \StuMP90\CovidCharts\ZCChartShortCodeHandler::getUKData("england",60);
                break;
            case 'england30':
                $case_data = \StuMP90\CovidCharts\ZCChartShortCodeHandler::getUKData("england",30);
                break;
            case 'usa90':
                $case_data = \StuMP90\CovidCharts\ZCChartShortCodeHandler::getUSAData("usa",90);
                break;
            case 'usa60':
                $case_data = \StuMP90\CovidCharts\ZCChartShortCodeHandler::getUSAData("usa",60);
                break;
            case 'usa30':
                $case_data = \StuMP90\CovidCharts\ZCChartShortCodeHandler::getUSAData("usa",30);
                break;
        }
        if (isset($case_data)) {
            $customchart['HistoryData'] = $case_data;
        }

        // Overide defaults
        $customchart = array_merge($customchart, $arguments);

        // Set template
        $template = new SSViewer('ZCChartShortCode');

        // Return template
        return $template->process(new ArrayData($customchart));
    }
    
    /**
     * Get historic data from the UK Government API
     * 
     * @param string $region
     * @param int $days
     *
     * @return arraylist
     */
    public static function getUKData(string $region, int $days = 30) {
        $list = ArrayList::create();

        if ($region = "england") {

            // UK Gov data source for England history
            $url = 'https://api.coronavirus.data.gov.uk/v1/data?filters=areaType=nation' . ";" . 'areaName=england&structure={%22date%22:%22date%22,%22newCases%22:%22newCasesByPublishDate%22,%22newDeaths%22:%22newDeaths28DaysByDeathDate%22,%22newAdmissions%22:%22newAdmissions%22,%22covidOccupiedMVBeds%22:%22covidOccupiedMVBeds%22,%22hospitalCases%22:%22hospitalCases%22}';

            // Use CURL to fetch data from API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            $json = curl_exec($ch);
            curl_close($ch);
            
            // Decode raw data from API
            $jshistorydec = json_decode($json,true);

            // Combined array to hold all data
            $combarr = array();

            // "data":[{"date":"2020-09-17","newCases":2788,"newDeaths":null}
            foreach ($jshistorydec['data'] as $histkey => $histval) {
                $pieces = explode('-',$histval['date']);
                $tlktme = mktime(0,0,0,$pieces[1],$pieces[2],$pieces[0]);
                $tlkdte = date("Y/m/d",$tlktme);
                $combarr[$tlktme]['dtestr'] = $tlkdte;

                // Firstly cases
                if ($histval['newCases'] > 0) {
                    // There can be multiple records for a single day from some of these APIs, so need to add them up
                    if (isset($combarr[$tlktme]['daycases'])) {
                        $combarr[$tlktme]['daycases'] = $combarr[$tlktme]['daycases'] + $histval['newCases'];
                    } else {
                        $combarr[$tlktme]['daycases'] = $histval['newCases'];
                    }
                } else {
                    if (!(isset($combarr[$tlktme]['daycases']))) {
                        $combarr[$tlktme]['daycases'] = 0;
                    }
                }

                // Then deaths
                if ($histval['newDeaths'] > 0) {
                    // There can be multiple records for a single day from some of these APIs, so need to add them up
                    if (isset($combarr[$tlktme]['daydeaths'])) {
                        $combarr[$tlktme]['daydeaths'] = $combarr[$tlktme]['daydeaths'] + $histval['newDeaths'];
                    } else {
                        $combarr[$tlktme]['daydeaths'] = $histval['newDeaths'];
                    }
                } else {
                    if (!(isset($combarr[$tlktme]['daydeaths']))) {
                        $combarr[$tlktme]['daydeaths'] = 0;
                    }
                }

                // Then hospital admissions
                if ($histval['newAdmissions'] > 0) {
                    // There can be multiple records for a single day from some of these APIs, so need to add them up
                    if (isset($combarr[$tlktme]['dayadmits'])) {
                        $combarr[$tlktme]['dayadmits'] = $combarr[$tlktme]['dayadmits'] + $histval['newAdmissions'];
                    } else {
                        $combarr[$tlktme]['dayadmits'] = $histval['newAdmissions'];
                    }
                } else {
                    if (!(isset($combarr[$tlktme]['dayadmits']))) {
                        $combarr[$tlktme]['dayadmits'] = 0;
                    }
                }
            }
            
            // Sort the array on the record's timestamp in reverse order, with
            // the most recent first
            arsort($combarr);
            $latest = array_slice($combarr, 0, ($days + 9));   // Get the latest 66 records (60 + 6 days for 7 day averages + 3 days for incomplete latest data)
            
            // Reverse the sequence for display
            asort($latest);
            
            // Process the array and generate 7 day averages
            if (is_array($latest) && (count($latest) > 0)) {
                $avgcaseshist = array();
                $avgdeathhist = array();
                $avgadmithist = array();
                foreach($latest as $key => $val) {
                    // Calculate 7 day averages
                    if (count($avgcaseshist) > 6) { // All arrays should be the same length
                        array_shift($avgcaseshist);
                        array_shift($avgdeathhist);
                        array_shift($avgadmithist);
                    }
                    $avgcaseshist[] = $val['daycases'];
                    $avgdeathhist[] = $val['daydeaths'];
                    $avgadmithist[] = $val['dayadmits'];
                    if(count($avgcaseshist)) {
                        $averagecases = round(array_sum($avgcaseshist) / count($avgcaseshist),0);
                    }
                    if(count($avgdeathhist)) {
                        $averagedeaths = round(array_sum($avgdeathhist) / count($avgdeathhist),0);
                    }
                    if(count($avgadmithist)) {
                        $averageadmits = round(array_sum($avgadmithist) / count($avgadmithist),0);
                    }

                   $return_data[$val['dtestr']] = array(
                        'dtetms' => $val['dtestr'],
                        'dtestr' => $val['dtestr'],
                        'daycases' => $val['daycases'],
                        'daydeaths' => $val['daydeaths'],
                        'dayadmits' => $val['dayadmits'],
                        'avgcases' => $averagecases,
                        'avgdeaths' => $averagedeaths,
                        'avgadmits' => $averageadmits
                    );
                }
                // Remove the first week for a stable average
                for ($x = 0; $x < 6; $x++) {
                    array_shift($return_data);
                }
                
                // Remove the last 3 days (incomplete data(
                for ($x = 0; $x < 3; $x++) {
                    array_pop($return_data);
                }
                
                // Loop through the results and return them as a Datalist
                foreach($return_data as $key => $val) {
                    $d = DataObject::create();
                    $d->Datestr = $val['dtestr'];
                    $d->DayCase = $val['daycases'];
                    $d->DayDeath = $val['daydeaths'];
                    $d->AvgCase = $val['avgcases'];
                    $d->AvgDeath = $val['avgdeaths'];
                    $list->push($d);
                }
            }

        }
        
        return $list;
    }
    
    /**
     * Get historic data from the UK Government API
     * 
     * @param string $region
     * @param int $days
     *
     * @return arraylist
     */
    public static function getUSAData(string $region, int $days = 60) {
        $list = ArrayList::create();

        if ($region = "usa") {

            // USA data source for USA history
            $url = "https://disease.sh/v3/covid-19/historical/usa?lastdays=all";

            // Use CURL to fetch data from API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            $json = curl_exec($ch);
            curl_close($ch);
            
            // Decode raw data from API
            $jshistorydec = json_decode($json,true);

            // As the data no longer includes recovery data, just use the cases and deaths data.
            $casesarr = array();
            $deatharr = array();

            // Data is cumulative and uses USA date formats, so we need to convert the date to something more reliable...

            // Firstly cases...
            foreach ($jshistorydec['timeline']['cases'] as $tlk => $tlv) {
                $pieces = explode('/',$tlk);
                $tlktme = mktime(0,0,0,$pieces[0],$pieces[1],$pieces[2]);
                $tlkdte = date("Y/m/d",$tlktme);
                // There can be multiple records for a single day from some of these APIs, so need to add them up
                if (isset($casesarr[$tlktme])) {
                    $casesarr[$tlktme] = $casesarr[$tlktme] + $tlv;
                } else {
                    $casesarr[$tlktme] = $tlv;
                }
            }

            // Then deaths...
            foreach ($jshistorydec['timeline']['deaths'] as $tlk => $tlv) {
                $pieces = explode('/',$tlk);
                $tlktme = mktime(0,0,0,$pieces[0],$pieces[1],$pieces[2]);
                $tlkdte = date("Y/m/d",$tlktme);
                // There can be multiple records for a single day from some of these APIs, so need to add them up
                if (isset($deatharr[$tlktme])) {
                    $deatharr[$tlktme] = $deatharr[$tlktme] + $tlv;
                } else {
                    $deatharr[$tlktme] = $tlv;
                }
            }

            // Sort the arrays on the record's timestamp
            asort($casesarr);
            asort($deatharr);

            // Combined array to hold all data
            $combarr = array();

            // Add daily and cumulative cases to combined array
            $prevtot = 0;
            foreach ($casesarr as $tlk => $tlv) {
                $combarr[$tlk]['dtestr'] = date("Y/m/d",$tlk);
                $combarr[$tlk]['cumcases'] = $tlv;
                $combarr[$tlk]['daycases'] = $tlv - $prevtot;
                $prevtot = $tlv;
            }

            // Add daily and cumulative deaths to combined array
            $prevtot = 0;
            foreach ($deatharr as $tlk => $tlv) {
                $combarr[$tlk]['dtestr'] = date("Y/m/d",$tlk);
                $combarr[$tlk]['cumdeaths'] = $tlv;
                $combarr[$tlk]['daydeaths'] = $tlv - $prevtot;
                $prevtot = $tlv;
            }
            
            // Sort the array on the record's timestamp in reverse order, with
            // the most recent first
            arsort($combarr);
            $latest = array_slice($combarr, 0, ($days + 9));   // Get the latest 66 records (60 + 6 days for 7 day averages + 3 days for incomplete latest data)
            
            // Reverse the sequence for display
            asort($latest);
            
            // Process the array and generate 7 day averages
            if (is_array($latest) && (count($latest) > 0)) {
                $avgcaseshist = array();
                $avgdeathhist = array();
                foreach($latest as $key => $val) {
                    // Calculate 7 day averages
                    if (count($avgcaseshist) > 6) { // All arrays should be the same length
                        array_shift($avgcaseshist);
                        array_shift($avgdeathhist);
                    }
                    $avgcaseshist[] = $val['daycases'];
                    $avgdeathhist[] = $val['daydeaths'];
                    if(count($avgcaseshist)) {
                        $averagecases = round(array_sum($avgcaseshist) / count($avgcaseshist),0);
                    }
                    if(count($avgdeathhist)) {
                        $averagedeaths = round(array_sum($avgdeathhist) / count($avgdeathhist),0);
                    }

                   $return_data[$val['dtestr']] = array(
                        'dtetms' => $val['dtestr'],
                        'dtestr' => $val['dtestr'],
                        'daycases' => $val['daycases'],
                        'daydeaths' => $val['daydeaths'],
                        'avgcases' => $averagecases,
                        'avgdeaths' => $averagedeaths,
                    );
                }
                // Remove the first week for a stable average
                for ($x = 0; $x < 6; $x++) {
                    array_shift($return_data);
                }
                
                // Remove the last 3 days (incomplete data(
                for ($x = 0; $x < 3; $x++) {
                    array_pop($return_data);
                }
                
                // Loop through the results and return them as a Datalist
                foreach($return_data as $key => $val) {
                    $d = DataObject::create();
                    $d->Datestr = $val['dtestr'];
                    $d->DayCase = $val['daycases'];
                    $d->DayDeath = $val['daydeaths'];
                    $d->AvgCase = $val['avgcases'];
                    $d->AvgDeath = $val['avgdeaths'];
                    $list->push($d);
                }
            }

        }
        
        return $list;
    }
    
}