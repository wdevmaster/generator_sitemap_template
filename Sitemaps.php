<?php 
	/**
	 * Que hace la class
	 * @author 
	 * @version  
	 **/

	require_once 'dbConfig.php';
	class Sitemaps 
	{
		public $idRest;			//Id de Restaurante
		public $namRest;		//Nombre del Restaurante
		public $idTypeLang;		//Id Lang Template de Restaurante
		public $idTypeMenu;		//Id Menu Template de Restaurante
		//public $TypePriorty;

		function __construct(){
			do{
				echo "\nIndica el Id del Restaurat:";
				$this->idRest = trim(fgets(STDIN));	//Lee consola -> Id Restaurante
			}while(empty($this->idRest));	//Repite hasta insertar un Numero

			self::nameRest($this->idRest); // llama funcion de nombres Restaurante

			echo "\n";	
		}

		//conexion DB
		function conx(){
			return mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
		}

		// consulta website Restaurante
		function nameRest($id){
			$sql = "SELECT * FROM restaurants WHERE id = '".$id."'";
			$reslt = mysqli_query(self::conx(),$sql);
			while ($row = mysqli_fetch_array($reslt))
			{ $this->namRest = substr($row['website'],4); }
		}

		//consulta branches de Restaurantes
		function branchesRest($id){
			$n = 0;
			$array = array();
			$sql = "SELECT id FROM branches WHERE restaurant_id = '".$id."'";
			$reslt = mysqli_query(self::conx(),$sql);
			while($row = mysqli_fetch_array($reslt)){
				$array[$n] = $row['id'];
			$n++; 
			}
		return $array;
		}
		
		//Consulta de Template de Restaurante
		function verifyTempl($id){
			$sql="SELECT idMenu, idLang FROM tp_templates WHERE idResto = '".$id."'";
			$reslt = mysqli_query(self::conx(),$sql);
			while($row = mysqli_fetch_array($reslt)){ 
				$this->idTypeLang=$row['idLang'];
				$this->idTypeMenu=$row['idMenu'];
			}
		}
		
		//Consulta de tipo de idioma que soporta
		function typeLang($id){
			$array = array (0 => false, 1 => false);
			$sql = "SELECT * FROM tp_type_language WHERE id = '".$id."'";
			$reslt = mysqli_query(self::conx(),$sql);

			//Id 1 y 3 -> En, Fr;
			//Id 2 -> En;
			//Id 4 -> Fr;

			while($row = mysqli_fetch_array($reslt)){
				if($row['id'] == 1 or $row['id'] == 3){ 
					$array[0] = 'en'; $array[1]= 'fr';
				}else{
					if($row['id'] == 2){ 
						$array[0] = 'en';
					}else{
						if($row['id'] == 4){ 
							$array[1]= 'fr';
						}
					}
				}
			}
			return $array;
		}

		//Consulta de opciones del Template del Restaurante
		function typeMen($id){
			$j = 0;
			//array de con nombre de opcion de base de datos
			$namRow = array(0 => 'home',
							1 => 'menu',
							2 => 'order_online',
							3 => 'aboutUs',
							4 => 'photos',
							5 => 'reserva',
							6 => 'contactUs',
							7 => 'services',
							8 => 'coupons',
							9 => 'blog',);

			$opc = array(); 

			$sql = "SELECT * FROM tp_type_menu WHERE id = '".$id."'";
			$reslt = mysqli_query(self::conx(),$sql);
			$row = mysqli_fetch_array($reslt);
			for($i=0; $i<count($namRow); $i++){
				if($row[$namRow[$i]] == 1){
					if($row[$namRow[$i].'_en'] == 'CONTACT'){
						$opc[$j] = 'customer-support';
					}else{
						$opc[$j] = strtolower($row[$namRow[$i].'_en']);	
					}
					$j++;
				}
			}
			return $opc;
		}

		// Genera Url de sitemaps y priority dependiendo de la opcion
		function arrayUrl(){
			$n = 0;
			$array = array();

			self::verifyTempl($this->idRest);
			$lang = self::typeLang($this->idTypeLang);
			$men = self::typeMen($this->idTypeMenu);
			$branc = self::branchesRest($this->idRest);

			for ($l=0; $l<count($lang); $l++){
				$p = 1.0;
				for ($m=0; $m<count($men); $m++){
					if ($lang[$l] != false) {
						if($men[$m] != 'order online'){
							if($men[$m] == 'menu'){
								for($b = 0; $b<count($branc); $b++){
									$array[$n] = $p."-http://".$this->namRest."/".$lang[$l]."/".
										   		  $men[$m]."/restaurant/".$this->idRest."/".
										   		  $branc[$b]."/Order-Online";	  
									$n++;
								}
							}else{
								if($men[$m] == 'flyer'){ //
									$array[$n] = $p."-http://".$this->namRest."/".$lang[$l].
												 "/menuViews";
									$n++;
									for($b = 0; $b<count($branc); $b++){
									   	$array[$n] = $p."-http://".$this->namRest."/".$lang[$l].
									   				 "/menu/restaurant/".$this->idRest."/".
									   				 $branc[$b]."/Order-Online";
									$n++;
									}	
								}else{
									if($men[$m] == 'photos ' or $men[$m] == 'customer-support')
									{ $p = 0.4; } 
									if($men[$m] == 'reservation' or $men[$m] == 'coupons')
									{ $p = 0.8; } 
									$array[$n] = $p."-http://".$this->namRest."/".
					 				  		$lang[$l]."/".$men[$m];
					 				$n++;
								}	
					 		}	
					 	} 
					 } 
				 }
			}

		return $array;
		}

		function fileXml(){
			date_default_timezone_set('UTC');

			$n = 0;
			$c = 0;

			$urlxml = self::arrayUrl();

			$file = fopen("sitemaps.xml","w");
			fwrite($file, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
			fwrite($file, "<urlset".PHP_EOL);
			fwrite($file, "      ");
			fwrite($file, 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'.PHP_EOL);
			fwrite($file, "      ");
			fwrite($file, 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.PHP_EOL);
			fwrite($file, "      ");
			fwrite($file, 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'.PHP_EOL);
			fwrite($file, "            ");
			fwrite($file, 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'.PHP_EOL);
			fwrite($file, "	<url>".PHP_EOL);
			fwrite($file, "		<loc>http://".$this->namRest."</loc>".PHP_EOL);
			fwrite($file, "		<lastmod>".date("Y-m-d")."</lastmod>".PHP_EOL);
			fwrite($file, "		<priority>1</priority>".PHP_EOL);
			fwrite($file, "	</url>".PHP_EOL);

			for($i=0; $i < count($urlxml); $i++){
				$n = strpos($urlxml[$i],'-');
				$c = strlen($urlxml[$i]);
				fwrite($file, "	<url>".PHP_EOL);
				fwrite($file, "		<loc>".substr($urlxml[$i],$n+1)."</loc>".PHP_EOL);
				fwrite($file, "		<lastmod>".date("Y-m-d")."</lastmod>".PHP_EOL);
				fwrite($file, "		<priority>".substr($urlxml[$i],0,$n-$c)."</priority>".PHP_EOL);
				fwrite($file, "	</url>".PHP_EOL);
			}
			fwrite($file, "</urlset>".PHP_EOL);
			fclose($file);
			if($file){echo "sitemaps.xml Creado con exito\n";}
			else{echo "ERROR al crear sitemaps.xml\n";}
		}

		function __destruct(){
			
			$n = 0;

			$urlxml = self::arrayUrl();
			for($i=0; $i < count($urlxml); $i++){
				
				$n = strpos($urlxml[$i],'-');
				echo substr($urlxml[$i],$n+1)."\n";

			}
			echo "\n";
			self::fileXml();
			echo "\n";
		}
	}
 ?>