<?php

class Profileolabs_Shoppingflux_Model_Export_Source_Tracking_Delay
{
    
   
    public function toOptionArray()
    {
        return array(
            array('label'=>10, 'value'=>10),
            array('label'=>9, 'value'=>9),
            array('label'=>8, 'value'=>8),
            array('label'=>7, 'value'=>7),
            array('label'=>6, 'value'=>6),
            array('label'=>5, 'value'=>5),
            array('label'=>4, 'value'=>4),
            array('label'=>3, 'value'=>3),
            array('label'=>2, 'value'=>2),
            array('label'=>1, 'value'=>1),
            array('label'=>'Immediate', 'value'=>0),
        );
        
    }
}
