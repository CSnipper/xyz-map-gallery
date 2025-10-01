<?php
// includes/import/csv.php
if ( ! defined('ABSPATH') ) exit;

function xyz_import_csv_detect_columns($path){
    $fh = fopen($path,'r');
    if (!$fh) return [];
    $hdr = fgetcsv($fh,0,',','"');
    fclose($fh);
    return is_array($hdr) ? $hdr : [];
}

function xyz_import_csv_run($path,$args){
    $map_id    = (int)($args['map_id'] ?? 0);
    $dry       = !empty($args['dry']);
    $overwrite = !empty($args['overwrite']);
    $mapping   = (array)($args['mapping'] ?? []);

    $fh = fopen($path,'r');
    if (!$fh) return ['imported'=>0,'updated'=>0,'skipped'=>0,'errors'=>['CSV open fail']];

    $hdr = fgetcsv($fh,0,',','"');
    $idx = [];
    foreach ($mapping as $target=>$col){
        $idx[$target] = ($col && ($i=array_search($col,$hdr,true))!==false) ? $i : -1;
    }

    $imported=0;$updated=0;$skipped=0;$errors=[];$log=[];

    while(($row=fgetcsv($fh,0,',','"'))!==false){
        $title = $idx['title']>=0 ? trim($row[$idx['title']]) : '';
        $desc  = $idx['description']>=0 ? trim($row[$idx['description']]) : '';
        $lat   = $idx['lat']>=0 ? (float)$row[$idx['lat']] : null;
        $lng   = $idx['lng']>=0 ? (float)$row[$idx['lng']] : null;
        if ($title==='' || !is_finite($lat) || !is_finite($lng)) { $skipped++; $errors[]="Bad row"; continue; }

        $owners = $idx['owner']>=0 ? trim($row[$idx['owner']]) : '';
        $tags   = $idx['tags']>=0  ? trim($row[$idx['tags']])  : '';
        $icon   = $idx['icon']>=0  ? trim($row[$idx['icon']])  : '';
        $ext_id = $idx['ext_id']>=0? trim($row[$idx['ext_id']]): '';

        $pos = sprintf('%.6F,%.6F',$lat,$lng);

        $res = xyz_import_upsert_marker([
            'title'=>$title,'description'=>$desc,
            'position'=>$pos,'owners'=>$owners,'tags'=>$tags,
            'icon'=>$icon,'ext_id'=>$ext_id,'map_id'=>$map_id,
        ], compact('dry','overwrite'));

        if (is_wp_error($res)){ $errors[]=$res->get_error_message(); $skipped++; }
        else {
            if ($res['action']==='created') $imported++;
            elseif ($res['action']==='updated') $updated++;
            else $skipped++;
            $log[]=$res['message'];
        }
    }
    fclose($fh);

    return compact('imported','updated','skipped','errors','log');
}
