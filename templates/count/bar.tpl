<?php

echo "<img src="http://chart.apis.google.com/chart?cht={$response->type}&chs={$response->size}&chds=a&chd=t:{$response->data}&chxl=1:|{$response->x_labels}&chxt=x,y&chtt={$response->title}" />";

 ?> 
