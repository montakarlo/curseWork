<?php
$widthdef=800; //ширина блока изображений
$heightdef=150; //максимальная высота одной строки
$margin=2; //отступы между картинками
$uploadsdir='./upload/'; //папка, в которой лежат изображения
//$img[от 1 до N] - массив картинок
//$imagescount=N соответсвенно, количество картинок
echo '<div style="width:'.$widthdef.'px;">';

$first=1;

while($first<=$imagescount){
	$images=$first-1;
	$hightes=$heightdef+1;
		while($hightes > $heightdef && $images<$imagescount) {
			$images++;
			$width=$widthdef-($images-$first+1)*($margin*2); //ширина,с учетом отсупов
			list($w[$images], $h[$images]) = getimagesize($uploadsdir.$img[$images]); //запрашиваем ширину и высоту изображения по мере необходимости

			$delim=$width*$h[$first];

			$delit=$w[$first];

			for($j=($first+1);$j<=$images;$j++) {
				$delit=$delit+$w[$j]*($h[$first]/$h[$j]);
			}
			$hightes=floor($delim/$delit);//высота строки


			if($hightes<=$heightdef) {
				for($i=$first;$i<=$images;$i++) {
					$ht=$hightes.'px';
					echo '<img style="margin:'.$margin.'px;" src="'.$uploadsdir.$img[$i].'" height="'.$ht.'">'; //выводим картинку

				}
				$first=$images+1;

			} else {

				if($images==$imagescount) {
			 //вывод картинок, если блок не получается полностью заполненным
					for($y=$first;$y<=$images;$y++) {
						echo '<img style="margin:'.$margin.'px;" src="'.$uploadsdir.$img[$y].'" height="'.$heightdef.'px">';
					}
					$first=$images+1; //указываем, с какой картинки считать
				}

			}

		}
}

echo '</div>';

?>
