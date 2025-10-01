<?php
// includes/import/geojson.php
if ( ! defined('ABSPATH') ) exit;

function xyz_import_geojson_detect_columns($path){
    $json = json_decode(file_get_contents($path),true);
    if (!$json) return [];
    $features = $json['features'] ?? [];
    if (!$features) return [];
    $props = $features[0]['properties'] ?? [];
    return array_keys($props);
}

function xyz_import_geojson_run($path,$args){
    $map_id    = (int)($args['map_id'] ?? 0);
    $dry       = !empty($args['dry']);
    $overwrite = !empty($args['overwrite']);
    $mapping   = (array)($args['mapping'] ?? []);

    $json = json_decode(file_get_contents($path),true);
    if (!$json) return ['imported'=>0,'updated'=>0,'skipped'=>0,'errors'=>['Bad JSON']];

    $features = $json['features'] ?? [];
    $imported=0;$updated=0;$skipped=0;$errors=[];$log=[];

    foreach($features as $i=>$f){
        if(($f['geometry']['type']??'')!=='Point'){ $skipped++; continue; }
        $coords=$f['geometry']['coordinates']??[];
        if(count($coords)<2){ $skipped++; continue; }
        $lng=(float)$coords[0]; $lat=(float)$coords[1];
        if(!is_finite($lat)||!is_finite($lng)){ $skipped++; continue; }

        $p=$f['properties']??[];
        $title = $mapping['title'] && isset($p[$mapping['title']]) ? $p[$mapping['title']] : '';
        $desc  = $mapping['description'] && isset($p[$mapping['description']]) ? $p[$mapping['description']] : '';
        if($title==='') $title="Marker $i";

        $owners=$mapping['owner'] && isset($p[$mapping['owner']])?$p[$mapping['owner']]:'';
        $tags  =$mapping['tags']  && isset($p[$mapping['tags']]) ?$p[$mapping['tags']]  :'';
        $icon  =$mapping['icon']  && isset($p[$mapping['icon']]) ?$p[$mapping['icon']]  :'';
        $ext_id=$mapping['ext_id']&& isset($p[$mapping['ext_id']])?$p[$mapping['ext_id']]:'';

        $pos=sprintf('%.6F,%.6F',$lat,$lng);

        $res=xyz_import_upsert_marker([
            'title'=>$title,'description'=>$desc,
            'position'=>$pos,'owners'=>$owners,'tags'=>$tags,
            'icon'=>$icon,'ext_id'=>$ext_id,'map_id'=>$map_id,
        ], compact('dry','overwrite'));

        if(is_wp_error($res)){ $errors[]="Feature#$i: ".$res->get_error_message(); $skipped++; }
        else {
            if ($res['action']==='created') $imported++;
            elseif ($res['action']==='updated') $updated++;
            else $skipped++;
            $log[]=$res['message'];
        }
    }

    return compact('imported','updated','skipped','errors','log');
}
