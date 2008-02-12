<?php
/** funcions de consulta directa
 */
function guifi_consulsql() {
echo hola;
echo hola;
echo hola;
echo hola;
echo hola;
echo hola;
echo hola;
echo hola;
echo hola;
echo hola;
echo hola;
echo hola;
echo adeu;
return;
}



function guifi_block2($op = "list", $delta = 0) {
  $oGC = new GeoCalc();
  $dTotals = array();
  

  if ($op == "list") {
    $blocks[0]["info"] = t(variable_get("guifi_title",t("guifi nodes")));
    return $blocks;
  }
  else {
    $block['subject'] = t(variable_get("guifi_title",
      "<a href=\"guifi\">" .t("List of guifi nodes") ."</a>"));
    $tnodes = db_query('
      SELECT 
        status_flag, count(*) c 
      FROM {guifi_location} 
      GROUP BY status_flag');
    $str = '';
    $rows = array();
    $totals = 0;
    while ($summary = db_fetch_object($tnodes)) {
      $rows[] = array(
        t($summary->status_flag),
        array(
          'data'=>
            number_format(
              $summary->c,
              0,
              null,
              variable_get(
                'guifi_thousand',
                ',')
            ),
            'class'=>$summary->status_flag));
      $totals = $totals + $summary->c;
    }
    $header = array(
      t('Nodes'),
      '<p align=RIGHT>'.
        number_format(
          $totals,
          0,
          null,
          variable_get('guifi_thousand','.')
        ).'</p>');
    $content = theme('table', $header, $rows);
    $content .= '<div class="more-link">'. 
      l(t('node list and maps...'),
        variable_get('guifi_root','/guifi_zones'), 
        array(
          'title' => t('Go to the list of nodes root page and their maps.'))) .'</div>';

    $qlinks = db_query('
      SELECT 
        l1.id, n1.id nid1, n2.id nid2, l1.link_type, n1.lat lat1, 
        n1.lon lon1, n2.lat lat2, n2.lon lon2 
      FROM guifi_links l1 
        LEFT JOIN guifi_links l2 ON l1.id=l2.id 
        LEFT JOIN guifi_location n1 ON l1.nid=n1.id 
        LEFT JOIN guifi_location n2 ON l2.nid=n2.id 
      WHERE l1.nid != l2.nid AND l1.device_id != l2.device_id');
    unset($listed);
    while ($link = db_fetch_object($qlinks)) {
      if (!isset($listed[$link->id]) )
        $listed[$link->id] = $link;
      else
        continue; 
      $d = 
        round($oGC->EllipsoidDistance(
          $link->lat1, 
          $link->lon1, 
          $link->lat2, 
          $link->lon2),
          1);
      switch ($link->link_type) {
        case 'wds': $type=t('PtP link'); break;
        case 'ap/client': $type=t('ap/client'); break;
        default: $type=t('unknown'); 
      }
      if ($d < 100) {
        $dTotals[$type]['dTotal'] += $d; 
        $dTotals[$type]['count'] ++;
      } else
       guifi_log(GUIFILOG_BASIC,sprintf('Probable DISTANCE error between nodes (%d and %d) %d kms.',
        $link->nid1,
        $link->nid2,
        $d));
    }

    unset($rows);
    $rows=array();
    if (count($dTotals)) foreach ($dTotals as $key=>$dTotal) 
    if ($dTotal['dTotal']) {
      $rows[] = array(
        $key,
        array(
          'data'=>number_format(
            $dTotal['count'],
            0,
            null,
            variable_get('guifi_thousand','.')),
          'align'=>'right'),
        array(
          'data'=>number_format(
            $dTotal['dTotal'],
            2,
            variable_get('guifi_decimal',','),
            variable_get('guifi_thousand','.')),
          'align'=>'right')
        );
      $lcount += $dTotal['count'];
      $ldTotal += $dTotal['dTotal'];
    }
    if ($lcount)
      $rows[] = array(
        '<strong>'.t('Total').'</strong>',
        array(
          'data'=>number_format(
            $lcount,
            0,
            null,
            variable_get('guifi_thousand','.')),
          'align'=>'right'),
        array(
          'data'=>number_format(
            $ldTotal,
            2,
            variable_get('guifi_decimal',','),
            variable_get('guifi_thousand','.')),
          'align'=>'right')
      );
    $content.= theme(
      'table',
      array(t('Wireless<br />links'),
      '<p align="right">#</p>',t('kms.')),$rows);

    $block['content'] = $content;
    return $block;

  }
}

?>
