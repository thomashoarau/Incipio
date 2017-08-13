<?php

namespace Mgate\StatBundle\Manager;

use Mgate\StatBundle\Controller\IndicateursController;
use Ob\HighchartsBundle\Highcharts\Highchart;

/**
 * Class ChartFactory.
 */
class ChartFactory
{
    public function newColumnChart($series, $categories)
    {
        $ob = new Highchart();
        // OTHERS
        $ob->chart->type('column');
        $ob->yAxis->min(0);
        $ob->yAxis->max(100);
        $style = IndicateursController::DEFAULT_STYLE;
        $ob->title->style(['fontWeight' => 'bold', 'fontSize' => '20px']);
        $ob->xAxis->labels(['style' => $style]);
        $ob->yAxis->labels(['style' => $style]);
        $ob->credits->enabled(false);
        $ob->legend->enabled(false);

        $ob->series($series);
        $ob->xAxis->categories($categories);

        $ob->title->text('Title');
        $ob->yAxis->title(['text' => 'Title y', 'style' => $style]);
        $ob->xAxis->title(['text' => 'Title x', 'style' => $style]);
        $ob->tooltip->headerFormat('<b>header Format</b><br />');
        $ob->tooltip->pointFormat('Point format');

        return $ob;
    }

    public function newPieChart($series)
    {
        $ob = new Highchart();

        $ob->plotOptions->pie(['allowPointSelect' => true, 'cursor' => 'pointer', 'showInLegend' => true, 'dataLabels' => ['enabled' => false]]);
        $ob->series($series);
        $ob->title->style(['fontWeight' => 'bold', 'fontSize' => '20px']);
        $ob->credits->enabled(false);
        $ob->title->text('Répartition des dépenses selon les comptes comptables (Mandat en cours)');
        $ob->tooltip->pointFormat('{point.percentage:.1f} %');

        return $ob;
    }
}
