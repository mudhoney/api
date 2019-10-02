<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * SunPy Science Data Download Script Generator
 *
 * @category Helper
 * @package  Helioviewer
 * @author   Jeff Stys <jeff.stys@nasa.gov>
 * @license  http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License 1.1
 * @link     https://github.com/Helioviewer-Project
 *
 */

require_once HV_ROOT_DIR.'/../src/Helper/SciScript.php';

class Helper_SunPy extends Helper_SciScript {

    function __construct($params, $roi=null) {
        $this->_localPath = '~/';

        parent::__construct($params, $roi);
    }

    function buildScript() {

        $this->_logUsageStatistic();

        $filename   = $this->_getScriptFilename();
        $provenance = $this->_getProvenanceComment();

        $DataSnippet  = $this->_getSDOSnippet();
        $DataSnippet .= $this->_getEITSnippet();
        $DataSnippet .= $this->_getLASCOSnippet();
        $DataSnippet .= $this->_getMDISnippet();
        $DataSnippet .= $this->_getSTEREOSnippet();
        $DataSnippet .= $this->_getSWAPSnippet();
        $DataSnippet .= $this->_getYohkohSnippet();
        $DataSnippet .= $this->_getHinodeXRTSnippet();
        $DataSnippet .= $this->_getHEKSnippet();

        $code = <<<EOD
# SunPy data download script
#
#    {$filename}
#
#
# (1) Helioviewer provenance information
# --------------------------------------
#
{$provenance}
#
#
# (2) The SunPy environment and commands used to find and acquire data
# ------------------------------------------------------------------------
#
# This script requires an up-to-date installation of SunPy (version 1.0 or
# higher).  To install SunPy, please follow the instructions at www.sunpy.org.
#
# This script is provided AS-IS. NOTE: It may require editing for it to
# work on your local system. Also, many of the commands included here
# have more sophisticated options that can be used to optimize your ability
# to acquire the relevant data. Please consult the relevant documentation
# for further information.
#
# IMPORTANT NOTE
# These scripts query for the records that Fido provides access to.  Data
# relevant to your request may be available elsewhere.  Likewise, the HEK
# provides only the feature and event information they have access to.
# Other features and events relevant to your request may be available
# elsewhere.
#
#
# (3) Executing the script
# ------------------------
#
# To run this script, type the following from inside a Python session
#
# execfile("{$filename}")
#
# or preprend the script with a path to it, for example,
#
# execfile("path/to/script/{$filename}")
#
# By default, data will be downloaded to your home directory
# unless you modify the value of the 'local_path' variable below.
#
#
# (4) Script
# ----------

import os
import astropy.units as u
import sunpy.version as version
from sunpy.net import Fido, attrs as a
from sunpy.net import hek

hek_client = hek.HEKClient()

if version.version < '1.0':
	raise ValueError('SunPy version 1.0 or higher is required to run this script.')

EOD;

        if ( is_null($this->_end) ) {
            $this->_end = $this->_start;
        }

        $code .= <<<EOD

#
# Search for data in the following date time range
#

tstart = '{$this->_start}'
tend   = '{$this->_end}'
EOD;

        $code .= <<<EOD


#
# Save data to the following path
#

local_path = '{:s}{:s}'.format(os.path.expanduser('~/'), '{file}')
{$DataSnippet}
#
# (5) End of Script
# -----------------
EOD;

        $this->_printScript($filename, $code);
    }

    private function _logUsageStatistic(){
		//Log Statistic
		include_once HV_ROOT_DIR.'/../src/Database/Statistics.php';
		$statistics = new Database_Statistics();
		$statistics->log("sciScript-SunPy");
    }

    private function _getScriptFilename() {
        date_default_timezone_set('UTC');

        $temp = str_replace( Array('/', '-', ':', ' UTC', ' '),
                             Array(',', ',', ',', '',     ','),
                             $this->_start );
        list($Y,$m,$d,$H,$i,$s) = explode(',',$temp);
        $str = date('Ymd_His', mktime($H,$i,$s,$m,$d,$Y) );

        if ( !is_null($this->_end) ) {

            $temp = str_replace( Array('/', '-', ':', ' UTC', ' '),
                                 Array(',', ',', ',', '',     ','),
                                 $this->_end );
            list($Y,$m,$d,$H,$i,$s) = explode(',',$temp);
            $end   = date('Ymd_His', mktime($H,$i,$s,$m,$d,$Y) );

            $str .= '__'.$end;
        }

        return 'helioviewer_sunpy_'.$str.'.py';
    }

    private function _getProvenanceComment() {
        require_once HV_ROOT_DIR.'/../src/Helper/DateTimeConversions.php';

        $now = str_replace(Array('T','.000Z'),
                           Array(' ',' UTC'),
                           getUTCDateString());

        $comment = "# Automatically generated by Helioviewer.org on ".$now.".\n"
                 . "# This script uses the Virtual Solar Observatory (VSO; www.virtualsolar.org)\n"
                 . "# and/or the Heliophysics Event Knowledgebase (HEK; www.lmsal.com/hek)\n"
                 . "# service ";

        // Movie id?
        if ( array_key_exists('movieId',$this->_params) && !is_null($this->_params['movieId']) ) {
            $comment .= "to download the original science data used to generate\n"
                     .  "# the ßHelioviewer.org movie: http://helioviewer.org/?movieId="
                     .   $this->_params['movieId'] . "\n;";
        } else {
            $comment .= "to download original science data.\n#";
        }

        return $comment;
    }

    private function _getEITSnippet() {
        $string = <<<EOD

#
# EIT data
#
EOD;
        $count = 0;
        foreach ( $this->_imageLayers as $i=>$layer) {
            if ( $layer['uiLabels'][1]['name'] == 'EIT' ) {
                $count++;

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }

                $string .= <<<EOD

result_eit_{$layer['uiLabels'][2]['name']} = Fido.search(a.Time(tstart, tend), \
    a.Instrument('eit'), a.Wavelength('{$layer['uiLabels'][2]['name']}','{$layer['uiLabels'][2]['name']}'))
data_eit_{$layer['uiLabels'][2]['name']}   = Fido.fetch(result_eit_{$layer['uiLabels'][2]['name']}, path=local_path)

EOD;
            }
        }
        if ($count == 0) {
            $string = '';
        }

        return $string;
    }

    private function _getLASCOSnippet() {
        $string = <<<EOD

#
# LASCO data
#

EOD;
        $count = 0;
        foreach ( $this->_imageLayers as $i=>$layer ) {
            if ( $layer['uiLabels'][1]['name'] == 'LASCO' ) {
                $count++;

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }

                $detector = strtolower($layer['uiLabels'][2]['name']);
                $string .= <<<EOD

result_lasco_{$detector} = Fido.search(a.Time(tstart, tend), \
    a.Instrument('lasco-{$detector}'))
data_lasco_{$detector}   = Fido.fetch(result_lasco_{$detector}, path=local_path)

EOD;
            }
        }
        if ( $count == 0 ) {
            $string = '';
        }

        return $string;
    }

    private function _getMDISnippet() {
        $string = <<<EOD

#
# MDI data
#

EOD;
        $count = 0;
        foreach ( $this->_imageLayers as $i=>$layer) {
            if ( $layer['uiLabels'][1]['name'] == 'MDI' ) {
                $count++;

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }


                if ( $layer['uiLabels'][2]['name'] == 'continuum' ) {
                    $physobs_str = ", a.Physobs('intensity')";
                }
                else if ( $layer['uiLabels'][2]['name'] == 'magnetogram' ) {
                    $physobs_str = ", a.Physobs('LOS_magnetic_field')";
                }

                $string .= <<<EOD

result_mdi = Fido.search(a.Time(tstart, tend), \
    a.Instrument('mdi'){$physobs_str})
data_mdi   = Fido.fetch(result_mdi, path=local_path)

EOD;
            }
        }
        if ($count == 0) {
            $string = '';
        }

        return $string;
    }

    private function _getSTEREOSnippet() {
        $string = <<<EOD

#
# STEREO data
#

EOD;
        $count = 0;
        foreach ( $this->_imageLayers as $i=>$layer ) {
            $observatory = strtolower(str_replace('-','',$layer['uiLabels'][0]['name']));
            $instrument  = strtolower($layer['uiLabels'][1]['name']);
            $detector    = strtolower($layer['uiLabels'][2]['name']);

            if ( $layer['uiLabels'][2]['name'] == 'EUVI' ) {
                $count++;

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }

                $string .= <<<EOD

result_{$observatory}_{$instrument}_{$detector}_{$layer['uiLabels'][3]['name']} = Fido.search(a.Time(tstart, tend), \
    a.Instrument('euvi'), a.Wavelength('{$layer['uiLabels'][3]['name']}','{$layer['uiLabels'][3]['name']}'))
data_{$observatory}_{$instrument}_{$detector}_{$layer['uiLabels'][3]['name']}   = Fido.fetch(result_{$observatory}_{$instrument}_{$detector}_{$layer['uiLabels'][3]['name']}, \
    path=local_path)

EOD;
            }
            else if ( $layer['uiLabels'][2]['name'] == 'COR1' ||
                      $layer['uiLabels'][2]['name'] == 'COR2' ) {

                $count++;

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }

                $string .= <<<EOD

result_{$observatory}_{$instrument}_{$detector} = Fido.search(a.Time(tstart, tend), \
    a.Instrument('{$instrument}'), a.Detector('{$detector}'))
data_{$observatory}_{$instrument}_{$detector}   = Fido.fetch(result_{$observatory}_{$instrument}_{$detector}, \
    path=local_path)

EOD;
            }
        }
        if ($count == 0) {
            $string = '';
        }

        return $string;
    }

    private function _getSWAPSnippet() {
        $string = <<<EOD

#
# SWAP data
#

EOD;
        $count = 0;
        foreach ( $this->_imageLayers as $i=>$layer) {
            if ( $layer['uiLabels'][1]['name'] == 'SWAP' ) {
                $count++;

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }

                $string .= <<<EOD

result_swap = Fido.search(a.Time(tstart, tend), a.Instrument('swap'), a.Wavelength(174*u.Angstrom, 174*u.Angstrom))
data_swap   = Fido.fetch(result_swap, path=local_path)

EOD;
            }
        }
        if ($count == 0) {
            $string = '';
        }

        return $string;
    }

    private function _getYohkohSnippet() {
        $string = <<<EOD

#
# Yohkoh data
#

EOD;
        $count = 0;
        foreach ( $this->_imageLayers as $i=>$layer) {
            if ( $layer['uiLabels'][1]['name'] == 'SXT' ) {
                $count++;

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }

                $string .= <<<EOD

result_sxt = Fido.search(a.Time(tstart, tend), a.Instrument('sxt'))
data_sxt   = Fido.fetch(result_sxt, path=local_path)

EOD;
                break;
            }
        }
        if ($count == 0) {
            $string = '';
        }

        return $string;
    }

    private function _getSDOSnippet() {
        $string = '';

        $AIAwaves = array();
        $HMIwaves = array();
        foreach ( $this->_imageLayers as $i=>$layer ) {
            if ( $layer['uiLabels'][1]['name'] == 'AIA' ) {
                $AIAwaves[] = $layer['uiLabels'][2]['name'];
            }
            else if ( $layer['uiLabels'][1]['name'] == 'HMI' ) {
                $HMIwaves[]  = $layer['uiLabels'][2]['name'];
            }
        }

        if ( count($AIAwaves) == 0 && count($HMIwaves) == 0 ) {
            return '';
        }

        $count = 0;
        foreach ( $this->_imageLayers as $i=>$layer ) {
            if ( $layer['uiLabels'][1]['name'] == 'AIA' ) {
                $count++;

                if ( $count == 1 ) {
                    $string .= <<<EOD


#
# AIA data
#    WARNING - Full disk only. This may be a lot of data.

EOD;
                }

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }

                $string .= <<<EOD

result_aia_{$layer['uiLabels'][2]['name']} = Fido.search(a.Time(tstart, tend), \
    a.Instrument('aia'), a.Wavelength({$layer['uiLabels'][2]['name']} * u.Angstrom,{$layer['uiLabels'][2]['name']} * u.Angstrom))
data_aia_{$layer['uiLabels'][2]['name']}   = Fido.fetch(result_aia_{$layer['uiLabels'][2]['name']} , path=local_path)

EOD;
            }
        }

        $count = 0;
        foreach ( $this->_imageLayers as $i=>$layer ) {
            if ( $layer['uiLabels'][1]['name'] == 'HMI' ) {
                $count++;

                if ( $count == 1 ) {
                    $string .= <<<EOD


#
# HMI data
#

EOD;
                }

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }

                if ( $layer['uiLabels'][2]['name'] == 'continuum' ) {
                    $physobs_str = ", a.Physobs('intensity')";
                }
                else if ( $layer['uiLabels'][2]['name'] == 'magnetogram' ) {
                    $physobs_str = ", a.Physobs('LOS_magnetic_field')";
                }

                $string .= <<<EOD

result_hmi = Fido.search(a.Time(tstart, tend), \
    a.Instrument('hmi'){$physobs_str})
data_hmi   = Fido.fetch(result_hmi, path=local_path)

EOD;
            }
        }

        return $string;
    }

    private function _getHinodeXRTSnippet(){
        $string = <<<EOD

#
# Hinode/XRT data
#

EOD;
        $count = 0;
        foreach ( $this->_imageLayers as $i=>$layer) {
            if ( $layer['uiLabels'][1]['name'] == 'XRT' ) {
                $count++;

                if ( is_null($this->_end) ) {
                    $tstart = $tend = str_replace('/','-',$layer['subDate']).' '.$layer['subTime'];
                    $string .= <<<EOD

tstart = '{$tstart}'
tend   = '{$tend}'

EOD;
                }

                $string .= <<<EOD

result_xrt = Fido.search(a.Time(tstart, tend), a.Instrument('xrt'))
data_xrt   = Fido.fetch(result_xrt, path=local_path)

EOD;
                break;
            }
        }
        if ($count == 0) {
            $string = '';
        }

        return $string;

    }

    private function _getHEKSnippet() {
        if ( count($this->_eventLayers) == 0 ||
            (count($this->_eventLayers) == 1 && $this->_eventLayers[0]['frms'] == null) ) {

            return '';
        }

        $string = <<<EOD


#
# Feature/Event data - downloadable via the HEK
#

EOD;

        if ( $this->_kb_archivid != '' ) {

            $string .= <<<EOD
if (sunpy.__version__ > 0.3):

    hek_data = hek_client.query( \
        hek.attrs.Misc['KB_Archivid'] == '{$this->_kb_archivid}' )

else:

EOD;
            foreach ( $this->_eventLayers as $i=>$layer ) {

                if ( $layer['frms'] == 'all' ) {
                    $string .= <<<EOD

    hek_data_{$layer['event_type']} = hek_client.query(hek.attrs.Time(tstart, tend), hek.attrs.{$layer['event_type']})

EOD;
                }
                else {
                    $frmArray = explode(',', $layer['frms']);

                    foreach($frmArray as $j=>$frm) {

                        $frm_decoded = str_replace('_',' ',$frm);

                        $string .= <<<EOD

    hek_data_{$layer['event_type']}_{$frm} = hek_client.query(hek.attrs.Time(tstart, tend), \
        hek.attrs.{$layer['event_type']}, hek.attrs.FRM.Name == '{$frm_decoded}')

EOD;
                    }
                }
            }

        }
        else {
            $frm_all_array = Array();

            foreach ( $this->_eventLayers as $i=>$layer ) {

                if ( $layer['frms'] == 'all' ) {
                    $frm_all_array[] = 'hek.attrs.'.$layer['event_type'];
                }
                else {
                    $frmArray = explode(',', $layer['frms']);

                    foreach($frmArray as $j=>$frm) {

                        $frm_decoded = str_replace('_',' ',$frm);

                        $string .= <<<EOD

hek_data_{$layer['event_type']}_{$frm} = hek_client.query(hek.attrs.Time(tstart, tend), \
    hek.attrs.{$layer['event_type']}, hek.attrs.FRM.Name == '{$frm_decoded}')

EOD;
                    }
                }
            }

            if ( count($frm_all_array) ) {
                $frm_all_string = implode(' | ', $frm_all_array);
                $string .= <<<EOD

hek_data = hek_client.query(hek.attrs.Time(tstart, tend), {$frm_all_string})
EOD;
            }
        }

        return $string;
    }
}
?>
