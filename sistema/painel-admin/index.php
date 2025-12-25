<?php


require_once('../conexao.php');
require_once('verificar.php');

$csrf_token = csrf_token();

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : '';

$data_atual = date('Y-m-d');
$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_mes = $ano_atual . "-" . $mes_atual . "-01";
$data_ano = $ano_atual . "-01-01";

$id_usuario = $_SESSION['id'];

if (@$_GET['pagina'] != "") {
  $menu = $_GET['pagina'];
} else {
  if (@$_SESSION['nivel'] == 'Administrador' || @$_SESSION['nivel'] == 'Secretario' || @$_SESSION['nivel'] == 'Tesoureiro' || @$_SESSION['nivel'] == 'Assessor') {
    $menu = 'home';
  } else {
    $menu = 'home_professor';
  }
}

if (@$_SESSION['nivel'] == 'Administrador' or @$_SESSION['nivel'] == 'Secretario' or @$_SESSION['nivel'] == 'Tesoureiro') {
  $ocultar = '';
} else {
  $ocultar = 'ocultar';
}


if (@$_SESSION['nivel'] == 'Secretario') {
  $ocultar2 = 'ocultar';
}


//RECUPERAR DADOS DO USUÁRIO
$query = $pdo->query("SELECT * FROM usuarios where id = '$id_usuario'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$nome_usuario = $res[0]['nome'];
$email_usuario = $res[0]['usuario'];
$nivel_usuario = $res[0]['nivel'];
$foto_usuario = $res[0]['foto'];
$cpf_usuario = $res[0]['cpf'];
$senha_usuario = $res[0]['senha'];



if (@$_SESSION['nivel'] == 'Tutor' || @$_SESSION['nivel'] == 'Parceiro' || @$_SESSION['nivel'] == 'Professor' || @$_SESSION['nivel'] == 'Vendedor') {
  $classe_f = 'ocultar';
} else {
  $classe_f = '';
}

$stmt = $pdo->query("SELECT * FROM cores_sistema ORDER BY nome_classe");
$cores = $stmt->fetchAll(PDO::FETCH_ASSOC);


$classeDesejada = 'topo_pagina';

$coress = [];
foreach ($cores as $item) {
  $coress[$item['nome_classe']] = $item['valor_cor'];
}

$bg_menu = $coress['menu_lateral'];
$topo_pagina = $coress['topo_pagina'];
$texto_menu = $coress['texto_menu'];
$texto_submenu = $coress['texto_submenu'];
$bg_menu_hover = $coress['bg_menu_hover'];
$_SESSION['last_activity'] = time();


?>
<!DOCTYPE HTML>
<html>

<head>
  <title><?php echo $nome_sistema ?></title>
  <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">


  <script
    type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>

  <!-- Bootstrap Core CSS -->
  <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
  <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous"> -->
  <!-- Custom CSS -->
  <link href="css/style.css" rel='stylesheet' type='text/css' />

  <!-- font-awesome icons CSS -->
  <link href="css/font-awesome.css" rel="stylesheet">
  <!-- //font-awesome icons CSS-->

  <!-- side nav css file -->
  <link href='css/SidebarNav.min.css' media='all' rel='stylesheet' type='text/css' />
  <!-- //side nav css file -->
  <!-- js-->
  <script src="js/jquery-1.11.1.min.js"></script>
    <script>
      window.CSRF_TOKEN = "<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>";
    (function () {
      function getToken() {
        return window.CSRF_TOKEN || '';
      }
      if (window.fetch) {
        var originalFetch = window.fetch;
        window.fetch = function (resource, init) {
          init = init || {};
          var headers = new Headers(init.headers || {});
          if (!headers.has('X-CSRF-Token')) {
            headers.set('X-CSRF-Token', getToken());
          }
          init.headers = headers;
          return originalFetch(resource, init);
        };
      }
      if (window.jQuery) {
        window.jQuery.ajaxSetup({
          beforeSend: function (xhr) {
            if (getToken()) {
              xhr.setRequestHeader('X-CSRF-Token', getToken());
            }
          }
        });
      }
      document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') {
          return;
        }
        if (form.querySelector('input[name=\"csrf_token\"]')) {
          return;
        }
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = getToken();
        form.appendChild(input);
      }, true);
      })();
    </script>
    <script>
      (function () {
        var sessionUserId = "<?php echo (int) ($_SESSION['id'] ?? 0); ?>";
        var key = 'active_user_id';
        try {
          var activeId = localStorage.getItem(key);
          if (!activeId) {
            localStorage.setItem(key, sessionUserId);
          } else if (activeId !== sessionUserId) {
            window.location.reload();
            return;
          }
          window.addEventListener('storage', function (e) {
            if (e.key === key && e.newValue && e.newValue !== sessionUserId) {
              window.location.reload();
            }
          });
        } catch (err) {
          // localStorage blocked or unavailable
        }
      })();
    </script>
  <script src="js/modernizr.custom.js"></script>

  <!--webfonts-->
  <link href="//fonts.googleapis.com/css?family=PT+Sans:400,400i,700,700i&amp;subset=cyrillic,cyrillic-ext,latin-ext"
    rel="stylesheet">
  <!--//webfonts-->

  <!-- chart -->
  <script src="js/Chart.js"></script>
  <!-- //chart -->

  <!-- Metis Menu -->
  <script src="js/metisMenu.min.js"></script>
  <script src="js/custom.js"></script>
  <script src="js/sweetalert2.js"></script>
  <link href="css/custom.css" rel="stylesheet">
  <!--//Metis Menu -->
  <style>
    #chartdiv {
      width: 100%;
      height: 295px;
    }

    .color-input-group {
      display: flex;
      align-items: center;
      gap: 6px;
    }
  </style>


  <style>
    /* ===== MENU LATERAL MODERNO ===== */

    .sidebar-left {
      background-color: #1e1e2f;
      color: #fff;
      font-family: 'Segoe UI', sans-serif;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.3);
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: #888 #1e1e2f;
    }

    .sidebar-left .navbar {
      background: none;
      border: none;
      margin: 0;
      padding: 0;
    }

    .sidebar-left .navbar-header h1 {
      font-size: 22px;
      color: #fff !important;
      margin: 0;
      margin-top: 20px;
      /* padding: 20px; */
      text-align: start;
      background-color: #2a2a40;
      /* border-bottom: 1px solid #333; */
    }

    .sidebar-left .navbar-header h1 .fa {
      margin-right: 10px;
    }

    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar-menu>li {
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .sidebar-menu a {
      display: flex;
      align-items: center;
      padding: 14px 20px;
      color: #ccc;
      text-decoration: none;
      transition: background-color 0.2s, color 0.2s;
      font-size: 15px;
    }

    .treeview li {
      background-color:
        <?= $bg_menu ?>
        !important;
    }

    .treeview li a {
      color:
        <?= $texto_submenu ?>
        !important;
    }
  </style>



  <!--pie-chart --><!-- index page sales reviews visitors pie chart -->
  <script src="js/pie-chart.js" type="text/javascript"></script>
  <script type="text/javascript">

    $(document).ready(function () {
      $('#demo-pie-1').pieChart({
        barColor: '#2dde98',
        trackColor: '#eee',
        lineCap: 'round',
        lineWidth: 8,
        onStep: function (from, to, percent) {
          $(this.element).find('.pie-value').text(Math.round(percent) + '%');
        }
      });

      $('#demo-pie-2').pieChart({
        barColor: '#8e43e7',
        trackColor: '#eee',
        lineCap: 'butt',
        lineWidth: 8,
        onStep: function (from, to, percent) {
          $(this.element).find('.pie-value').text(Math.round(percent) + '%');
        }
      });

      $('#demo-pie-3').pieChart({
        barColor: '#ffc168',
        trackColor: '#eee',
        lineCap: 'square',
        lineWidth: 8,
        onStep: function (from, to, percent) {
          $(this.element).find('.pie-value').text(Math.round(percent) + '%');
        }
      });


    });

  </script>
  <!-- //pie-chart --><!-- index page sales reviews visitors pie chart -->

  <!-- requried-jsfiles-for owl -->
  <link href="css/owl.carousel.css" rel="stylesheet">
  <script src="js/owl.carousel.js"></script>
  <script>
    $(document).ready(function () {
      $("#owl-demo").owlCarousel({
        items: 3,
        lazyLoad: true,
        autoPlay: true,
        pagination: true,
        nav: true,
      });
    });
  </script>
  <!-- //requried-jsfiles-for owl -->
</head>


<body class="cbp-spmenu-push">


  <div class="main-content">


    <div class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-left" id="cbp-spmenu-s1">
      <!--left-fixed -navigation-->
      <aside class="sidebar-left">
        <nav class="navbar navbar-inverse"
          style="overflow: scroll; height:100%; scrollbar-width: thin; background-color: <?php echo $bg_menu ?>;">
          <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".collapse"
              aria-expanded="false">
              <span class="sr-only ">Menu</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <h1><a class="navbar-brand" href="index.php"><span class="fa fa-book">

                </span> <?php echo $nome_sistema ?><span class="dashboard_text"></span></a></h1>
          </div>
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="sidebar-menu">

              <li class="treeview li-menu <?= empty($pagina) ? 'active' : '' ?>">
                <a href="index.php">
                  <i class="fa fa-home"></i> <span class="text-menu">Home</span>
                </a>
              </li>


              <li
                class="treeview li-menu <?php echo $ocultar ?> <?= in_array($pagina, ['matriculas', 'matriculas_aprovadas']) ? 'active' : '' ?>">
                <a href="#">
                  <i class="fa fa-envelope-o"></i>
                  <span class="text-menu">Matrículas</span>
                  <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu ">
                  <li><a href="index.php?pagina=matriculas"><i class="fa fa-angle-right"></i>
                      Matriculas Pendentes</a></li>

                  <li><a href="index.php?pagina=matriculas_aprovadas"><i class="fa fa-angle-right"></i> Matriculas
                      Aprovadas</a></li>



                </ul>
              </li>

              <li
                class="treeview li-menu <?= in_array($pagina, ['alunos', 'administradores', 'assessores', 'secretarios', 'parceiros', 'professores', 'vendedores', 'tutores', 'tesoureiros', 'usuarios']) ? 'active' : '' ?>">
                <a href="#">
                  <i class="fa fa-users"></i>
                  <span class="text-menu">Pessoas</span>
                  <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                  <li><a href="index.php?pagina=alunos"><i class="fa fa-angle-right "></i> Alunos</a>
                  </li>

                  <?php if ($nivel_usuario == 'Tutor') { ?>
                    <li><a href="index.php?pagina=atendimentos_alunos"><i class="fa fa-angle-right "></i>Outros Alunos</a>
                    </li>
                  <?php } ?>
                  <?php if ($nivel_usuario == 'Secretario' || $nivel_usuario == 'Tesoureiro') { ?>
                    <li><a href="index.php?pagina=atendimentos_novo"><i class="fa fa-angle-right "></i>Meus Alunos</a>
                    </li>
                  <?php } ?>

                  <!--<?php if ($nivel_usuario == 'Tutor') { ?>-->
                    <!--  <li><a href="index.php?pagina=atendimentos_alunos"><i class="fa fa-angle-right "></i>Outros Alunos</a>-->
                    <!--  </li>-->
                    <!--<?php } ?>-->

                  <li class=" <?php echo $ocultar2 ?><?php echo $classe_f ?>"><a
                      href="index.php?pagina=administradores"><i class="fa fa-angle-right"></i>
                      Administradores</a></li>
                  <li class=" <?php echo $classe_f ?>"><a href="index.php?pagina=assessores"><i
                        class="fa fa-angle-right"></i>Assessores</a></li>

                  <li class=" <?php echo $ocultar2 ?><?php echo $classe_f ?>"><a href="index.php?pagina=secretarios"><i
                        class="fa fa-angle-right"></i>
                      Secretarios</a></li>

                  <li class=" <?php echo $classe_f ?>"><a href="index.php?pagina=parceiros"><i
                        class="fa fa-angle-right"></i>Parceiros</a></li>

                  <li class=" <?php echo $classe_f ?>"><a href="index.php?pagina=professores"><i
                        class="fa fa-angle-right "></i>Professores</a></li>

                  <li class=" <?php echo $classe_f ?>"><a href="index.php?pagina=vendedores"><i
                        class="fa fa-angle-right "></i>Vendedores</a></li>

                  <li class=" <?php echo $classe_f ?>"><a href="index.php?pagina=tutores"><i
                        class="fa fa-angle-right"></i>Tutores</a></li>

                  <li class=" <?php echo $ocultar2 ?><?php echo $classe_f ?>"><a href="index.php?pagina=tesoureiros"><i
                        class="fa fa-angle-right"></i>
                      Tesoureiros</a></li>

                  <li class=" <?php echo $ocultar2 ?><?php echo $classe_f ?>"><a href="index.php?pagina=usuarios"><i
                        class="fa fa-angle-right"></i>
                      Usuários</a></li>
                </ul>
              </li>


              <li
                class="treeview li-menu <?php echo $ocultar2 ?><?php echo $classe_f ?> <?= in_array($pagina, ['cursos', 'categorias', 'grupos', 'linguagens', 'pacotes']) ? 'active' : '' ?>">
                <a href="#">
                  <i class="fa fa-book"></i>
                  <span class="text-menu">Cursos / Pacotes</span>
                  <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                  <li><a href="index.php?pagina=cursos"><i class="fa fa-angle-right"></i> Cursos</a>
                  </li>

                  <li class="<?php echo $ocultar ?>"><a href="index.php?pagina=categorias"><i
                        class="fa fa-angle-right"></i> Categorias</a></li>
                  <li class="<?php echo $ocultar ?>"><a href="index.php?pagina=grupos"><i class="fa fa-angle-right"></i>
                      Grupos</a></li>
                  <li class="<?php echo $ocultar ?>"><a href="index.php?pagina=linguagens"><i
                        class="fa fa-angle-right"></i> Linguagens</a></li>
                  <li><a href="index.php?pagina=pacotes"><i class="fa fa-angle-right"></i> Pacotes</a>
                  </li>



                </ul>
              </li>

              <li class="treeview <?php echo $ocultar ?>">
                <a href="index.php?pagina=cupons">
                  <i class="fa fa-money"></i> <span class="text-menu">Cupom de Desconto</span>
                </a>
              </li>


              <li class="treeview li-menu <?php echo $ocultar ?> <?php echo $ocultar2 ?>">
                <a href="#">
                  <i class="fa fa-cog"></i>
                  <span class="text-menu">Recursos / Ferramentas</span>
                  <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                  <li><a href="index.php?pagina=banner_login"><i class="fa fa-angle-right"></i> Banner
                      Login</a></li>

                  <li><a href="index.php?pagina=banner_index"><i class="fa fa-angle-right"></i> Banner
                      Index</a></li>

                  <li><a href="index.php?pagina=email_marketing"><i class="fa fa-angle-right"></i>
                      Email Marketing</a></li>

                  <li><a href="paginas/email_marketing/script-enviar.php" target="_blank"><i
                        class="fa fa-angle-right"></i> Script Campanha</a></li>


                  <li><a href="index.php?pagina=alertas"><i class="fa fa-angle-right"></i> Alertas</a>
                  </li>



                </ul>
              </li>



              <li class="treeview li-menu <?php echo $ocultar ?> <?php echo $ocultar2 ?>">
                <a href="#">
                  <i class="fa fa-usd"></i>
                  <span class="text-menu">Financeiro</span>
                  <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                  <li><a href="index.php?pagina=vendas"><i class="fa fa-angle-right"></i> Vendas</a>
                  </li>

                  <li><a href="index.php?pagina=pagar"><i class="fa fa-angle-right"></i> Contas à
                      Pagar</a></li>

                  <li><a href="index.php?pagina=receber"><i class="fa fa-angle-right"></i> Contas à
                      Receber</a></li>

                  <li><a href="index.php?pagina=movimentacoes"><i class="fa fa-angle-right"></i>
                      Movimentações</a></li>


                  <li><a href="index.php?pagina=asaas_comissoes"><i class="fa fa-angle-right"></i>
                      Comissões</a></li>


                </ul>
              </li>


              <li class="treeview li-menu <?php echo $ocultar ?> <?php echo $ocultar2 ?>">
                <a href="#">
                  <i class="fa fa-file-pdf-o"></i>
                  <span class="text-menu">Relatórios Financeiros</span>
                  <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                  <li><a href="#" data-toggle="modal" data-target="#RelVen"><i class="fa fa-angle-right"></i> Vendas</a>
                  </li>

                  <li><a href="#" data-toggle="modal" data-target="#RelCon"><i class="fa fa-angle-right"></i> Contas</a>
                  </li>

                  <li><a href="#" data-toggle="modal" data-target="#RelLucro"><i class="fa fa-angle-right"></i>
                      Detalhamento de Lucro</a></li>

                  <li><a href="#" data-toggle="modal" data-target="#RelComissoes"><i class="fa fa-angle-right"></i>
                      Comissões</a></li>
                      
                  <li><a href="index.php?pagina=relatorio_alunos"><i class="fa fa-angle-right"></i>
                  Relatórios de Alunos</a></li>
                  
                   <li><a href="index.php?pagina=relatorio_alunos_efi"><i class="fa fa-angle-right"></i>
                  Relatórios de Alunos EFI</a></li>


                </ul>
              </li>



              <li class="treeview li-menu <?php echo $ocultar2 ?> <?php echo $classe_f ?>">
                <a href="index.php?pagina=perguntas">
                  <i class="fa fa-question"></i> <span class="text-menu">Perguntas Pendentes</span>
                </a>
              </li>
              
               <li class="treeview li-menu <?php echo $classe_f ?>">

                <a href="index.php?pagina=certificados">

                  <i class="fa fa-graduation-cap"></i> <span class="text-menu">Certificados</span>

                </a>

              </li>


              <?php if ($nivel_usuario == 'Professor' || $nivel_usuario == 'Tutor' || $nivel_usuario == 'Parceiro' || $nivel_usuario == 'Assessor' || $nivel_usuario == 'Vendedor') { ?>
                <li class="treeview">
                  <a href="index.php?pagina=minhas_comissoes">
                    <i class="fa fa-usd"></i> <span class="text-menu">Minhas Comissões</span>
                  </a>
                </li>
              <?php } ?>


              <li class="treeview li-menu <?php echo $ocultar ?> <?php echo $ocultar2 ?>">
                <a href="backup/backup.php" target="_blank">
                  <i class="fa fa-database"></i> <span class="text-menu">Backup</span>
                </a>
              </li>

              <li class="treeview li-menu <?php echo $ocultar ?> <?php echo $ocultar2 ?>">
                <a href="index.php?pagina=gateway">
                  <i class="fa fa-money"></i> <span class="text-menu">Gateway</span>
                </a>
              </li>

              <li class="treeview">
                <a href="../../" target="_blank">
                  <i class="fa fa-globe"></i> <span class="text-menu">Ir para o Site</span>
                </a>
              </li>

            </ul>
          </div>
          <!-- /.navbar-collapse -->
        </nav>
      </aside>
    </div>
    <!--left-fixed -navigation-->



    <!-- header-starts -->
    <div class="sticky-header header-section">

      <div class="header-left">

        <?php
        $total_respondidas = 0;
        //listar notificações das perguntas que os cursos pertencem ao professor
        $query = $pdo->query("SELECT * FROM perguntas where respondida != 'Sim'");
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        for ($i = 0; $i < @count($res); $i++) {
          foreach ($res[$i] as $key => $value) {
          }

          $id_curso = $res[$i]['curso'];
          $query2 = $pdo->query("SELECT * FROM cursos where id = '$id_curso' and professor = '$id_usuario'");
          $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
          if (@count($res2) > 0) {

            $total_respondidas += 1;
          }

        }



        if ($total_respondidas == 0) {
          $classe_badge = 'fundo-verde';
        } else {
          $classe_badge = 'red';
        }





        ?>

        <div class="openCloseMenu" id="newToggleMenu">

          <button id="showLeftPush" class=""><i class="fa fa-bars"></i></button>
          <div id="notificationIcon" class="profile_details_left">
            <ul class="nofitications-dropdown">

              <li class="dropdown head-dpdn">
                <a title="Perguntas Pendentes" href="index.php?pagina=perguntas" class="dropdown-toggle"><i
                    class="fa fa-bell"></i><span
                    class="badge <?php echo $classe_badge ?>"><?php echo $total_respondidas ?></span></a>

              </li>

            </ul>
            <div class="clearfix"> </div>
          </div>
        </div>



        <div class="clearfix"> </div>
      </div>

      <div class="header-right">


        <div class="profile_details">
          <ul>
            <li class="dropdown profile_details_drop">

              <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <div class="profile_img">
                  <span class="prfil-img"><img src="img/perfil/<?php echo $foto_usuario ?>" alt="" width="50px"
                      height="50px"> </span>
                  <div class="user-name">
                    <p><?php echo $nome_usuario ?></p>
                    <span><?php echo $nivel_usuario ?></span>
                  </div>
                  <i class="fa fa-angle-down lnr"></i>
                  <i class="fa fa-angle-up lnr"></i>
                  <div class="clearfix"></div>
                </div>
              </a>
              <ul class="dropdown-menu drp-mnu">

                <li>
                  <a href="" data-toggle="modal" data-target="#modalPerfil">
                    <i class="fa fa-user"></i>
                    Editar Perfil
                  </a>
                </li>



                <li class="treeview li-menu <?php echo $ocultar2 ?> <?php echo $classe_f ?>">
                  <a href="" data-toggle="modal" data-target="#modalConfig">
                    <i class="fa fa-cog"></i> Configurações</a>
                </li>

                <li> <a href="../logout.php"><i class="fa fa-sign-out"></i> Logout</a> </li>

              </ul>
            </li>
          </ul>
        </div>
        <div class="clearfix"> </div>
      </div>
      <div class="clearfix"> </div>
    </div>
    <!-- //header-ends -->




    <!-- main content start-->
    <div id="page-wrapper">
      <div class="main-page">
        <?php
        require_once('paginas/' . $menu . '.php');
        ?>

      </div>
    </div>



  </div>



  <!-- Classie --><!-- for toggle left push menu script -->
  <script src="js/classie.js"></script>
  <script>
    var menuLeft = document.getElementById('cbp-spmenu-s1'),
      showLeftPush = document.getElementById('showLeftPush'),
      body = document.body;

    let menu = document.getElementById("newToggleMenu");



    showLeftPush.onclick = function () {
      if (menu.classList.contains('openCloseMenu')) {
        menu.classList.remove('openCloseMenu');
        menu.classList.add('openCloseMenuAfter');
      } else {
        menu.classList.remove('openCloseMenuAfter');
        menu.classList.add('openCloseMenu');
      }
      classie.toggle(this, 'active');
      classie.toggle(body, 'cbp-spmenu-push-toright');
      classie.toggle(menuLeft, 'cbp-spmenu-open');
      disableOther('showLeftPush');
    };


    function disableOther(button) {
      if (button !== 'showLeftPush') {
        classie.toggle(showLeftPush, 'disabled');
      }
    }
  </script>
  <!-- //Classie --><!-- //for toggle left push menu script -->

  <!--scrolling js-->
  <script src="js/jquery.nicescroll.js"></script>
  <script src="js/scripts.js"></script>
  <!--//scrolling js-->

  <!-- side nav js -->
  <script src='js/SidebarNav.min.js' type='text/javascript'></script>
  <script>
    $('.sidebar-menu').SidebarNav()
  </script>
  <!-- //side nav js -->




  <!-- Bootstrap Core JavaScript -->
  <script src="js/bootstrap.js"> </script>
  <!-- //Bootstrap Core JavaScript -->

</body>

</html>






<!-- Modal -->
<div class="modal fade" id="modalPerfil" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="exampleModalLabel">Editar Dados</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" id="form-usu">
        <div class="modal-body">

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" class="form-control" name="nome_usu" value="<?php echo $nome_usuario ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>CPF</label>
                <input type="text" class="form-control" id="cpf_usu" name="cpf_usu" value="<?php echo $cpf_usuario ?>"
                  required>
              </div>
            </div>

          </div>


          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email_usu" value="<?php echo $email_usuario ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Senha</label>
                <input type="password" class="form-control" name="senha_usu" value="<?php echo $senha_usuario ?>"
                  required>
              </div>
            </div>

          </div>


          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Foto do usuario</label>
                <input class="form-control" type="file" name="foto" onChange="carregarImgPerfil();" id="foto-usu">
              </div>
            </div>
            <div class="col-md-4">
              <div id="divImg">
                <img src="img/perfil/<?php echo $foto_usuario ?>" width="100px" id="target-usu">
              </div>
            </div>

          </div>

          <input type="hidden" name="id_usu" value="<?php echo $id_usuario ?>">
          <input type="hidden" name="foto_usu" value="<?php echo $foto_usuario ?>">

          <small>
            <div id="mensagem-usu" align="center" class="mt-3"></div>
          </small>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Editar Dados</button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- Modal -->
<div class="modal fade" id="modalLayouts" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="exampleModalLabel">Editar Cores do Sistema</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" id="form-usu">
        <div class="modal-body">

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" class="form-control" name="nome_usu" value="<?php echo $nome_usuario ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>CPF</label>
                <input type="text" class="form-control" id="cpf_usu" name="cpf_usu" value="<?php echo $cpf_usuario ?>"
                  required>
              </div>
            </div>

          </div>


          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email_usu" value="<?php echo $email_usuario ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Senha</label>
                <input type="password" class="form-control" name="senha_usu" value="<?php echo $senha_usuario ?>"
                  required>
              </div>
            </div>

          </div>


          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Foto do usuario</label>
                <input class="form-control" type="file" name="foto" onChange="carregarImgPerfil();" id="foto-usu">
              </div>
            </div>
            <div class="col-md-4">
              <div id="divImg">
                <img src="img/perfil/<?php echo $foto_usuario ?>" width="100px" id="target-usu">
              </div>
            </div>

          </div>

          <input type="hidden" name="id_usu" value="<?php echo $id_usuario ?>">
          <input type="hidden" name="foto_usu" value="<?php echo $foto_usuario ?>">

          <small>
            <div id="mensagem-usu" align="center" class="mt-3"></div>
          </small>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Editar Dados</button>
        </div>
      </form>

    </div>
  </div>
</div>




<!-- <div class="modal fade" id="modalLayout" tabindex="-1" role="dialog" aria-labelledby="modalCoresLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="modalCoresLabel">Editar Cores do Sistema</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="post" id="form-cores">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="nome_classe">Nome da Classe</label>
                <input type="text" class="form-control" name="nome_classe" id="nome_classe"
                  placeholder="Ex: primary-color, btn-success" required>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="codigo_cor">Código da Cor</label>
                <div class="color-input-group">
                  <input type="text" class="form-control" name="codigo_cor" id="codigo_cor" placeholder="#FF5733"
                    required>
                  <input type="color" class="color-picker" id="color-picker" onchange="updateColorInput()">
                </div>
              </div>
            </div>
          </div>

       
          <input type="hidden" name="id_cor" id="id_cor">
          <input type="hidden" name="acao" value="salvar_cor">

          <small>
            <div id="mensagem-cores" align="center" class="mt-3"></div>
          </small>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div> -->



<!-- Modal -->
<div class="modal fade" id="modalLayout" tabindex="-1" role="dialog" aria-labelledby="modalCoresLabel"
  aria-hidden="true" style="z-index: 9999;">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title" id="modalCoresLabel">Editar Cores do Sistema</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="post" id="form-cores">
        <div class="modal-body">
          <div id="cores-container">
            <div id="campos-cores">
              <?php foreach ($cores as $index => $cor): ?>
                <div class="row mb-3 cor-item">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><?= htmlspecialchars($cor['nome_item']) ?></label>
                      <input type="text" class="form-control" name="nome_classe[]"
                        value="<?= htmlspecialchars($cor['nome_classe']) ?>" required>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Código da Cor</label>
                      <div class="color-input-group d-flex align-items-center gap-2">
                        <input type="text" class="form-control" name="valor_cor[]"
                          value="<?= htmlspecialchars($cor['valor_cor']) ?>" required>
                        <input type="color" class="color-picker" value="<?= htmlspecialchars($cor['valor_cor']) ?>"
                          onchange="this.previousElementSibling.value = this.value">
                      </div>
                    </div>
                  </div>
                  <input type="hidden" name="id_cor[]" value="<?= $cor['id'] ?>">
                  <input type="hidden" name="id_cor[]" value="">

                  <!-- <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm remove-cor">Remover</button>
                  </div> -->
                </div>
              <?php endforeach; ?>
            </div>
          </div>


          <div id="cor-template"></div>


          <small>
            <div id="mensagem-cores" align="center" class="mt-3"></div>
          </small>

          <input type="hidden" name="acao" value="salvar_cor">
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>

    </div>
  </div>
</div>





<!-- Modal Config-->

<div class="modal fade" id="modalConfig" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header btn-primary text-white">
        <h4 class="modal-title" id="exampleModalLabel">Editar Configurações</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
          style="color: white;  margin-top: -20px;">
          <span style="font-size: x-large;" aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" id="form-config">
        <div class="modal-body">
          <!-- Nav tabs -->
          <ul class="nav nav-tabs" id="configTabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="config-tab" data-toggle="tab" href="#config" role="tab"
                aria-controls="config" aria-selected="true">Configurações</a>
            </li>
            <li class="nav-item">
              <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab" aria-controls="basic"
                aria-selected="false">🛠 Dados Básicos</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="numeric-tab" data-toggle="tab" href="#numeric" role="tab" aria-controls="numeric"
                aria-selected="false">📈 Valores e Porcentagens</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="uploads-tab" data-toggle="tab" href="#uploads" role="tab" aria-controls="uploads"
                aria-selected="false">📤 Uploads</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="security-tab" data-toggle="tab" href="#security" role="tab"
                aria-controls="security" aria-selected="false">🔒 Segurança</a>
            </li>
            <li class="nav-item">

              <a href="" data-toggle="modal" data-target="#modalLayout">
                <i class="fa fa-cog"></i> Cores</a>
            </li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content p-3 border-left border-right border-bottom mb-3">



            <!-- Tab 4: Security -->
            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="card mb-4">
                      <div class="card-header bg-light">
                        <div style="display: flex; flex-direction: row; align-items: center; gap: 6px;">
                          <img src="https://www.asaas.com/assets/favicon-14e590c1a04095a343f34c60b0456560.ico"
                            width="20px" />
                          <h3>Asaas Pagamentos</h3>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div style="margin-top: 22px; padding: 22px;">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label>CHAVE ASAAS PROD</label>
                        <input style="margin-top: 6px;" type="text" class="form-control" id="chave_asaas"
                          name="chave_asaas" value="<?php echo $chave_asaas ?>"
                          placeholder="Digite sua chave Asaas aqui...">
                      </div>
                    </div>
                  </div>

                </div>

                <div>
                  <div class="row">





                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="card mb-4">
                        <div class="card-header bg-light">
                          <div style="display: flex; flex-direction: row; align-items: center; gap: 6px;">
                            <img
                              src="https://http2.mlstatic.com/frontend-assets/mp-web-navigation/ui-navigation/6.7.64/mercadopago/favicon.svg"
                              width="20px" />
                            <h3>Mercado Pago</h3>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div style="margin-top: 22px; padding: 22px;">
                      <div class="col-md-12">
                        <div class="form-group">
                          <label>CHAVE MERCADO PAGO</label>
                          <input style="margin-top: 6px;" type="text" class="form-control" id="mp_key" name="mp_key"
                            value="" placeholder="Digite sua chave Mercado Pago aqui...">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- Tab 3: Uploads -->
            <div class="tab-pane fade" id="uploads" role="tabpanel" aria-labelledby="uploads-tab">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="card mb-4">
                      <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fa fa-image mr-2"></i>Logo do Sistema</h5>
                      </div>
                      <div class="card-body text-center">
                        <div id="divImgLogo" class="mb-3">
                          <img src="../img/logo.png" class="img-thumbnail" style="max-height: 100px;" id="target-logo">
                        </div>
                        <div class="custom-file">
                          <input type="file" class="custom-file-input" name="logo" onChange="carregarImgLogo();"
                            id="foto-logo">
                          <label class="custom-file-label" for="foto-logo">Escolher
                            arquivo</label>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="card mb-4">
                      <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fa fa-star mr-2"></i>Favicon (ico)</h5>
                      </div>
                      <div class="card-body text-center">
                        <div id="divImgFavicon" class="mb-3">
                          <img src="../img/favicon.ico" class="img-thumbnail" style="max-height: 50px;"
                            id="target-favicon">
                        </div>
                        <div class="custom-file">
                          <input type="file" class="custom-file-input" name="favicon" onChange="carregarImgFavicon();"
                            id="foto-favicon">
                          <label class="custom-file-label" for="foto-favicon">Escolher
                            arquivo</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="card mb-4">
                      <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fa fa-file-image mr-2"></i>Imagem Relatório
                          (*jpg)</h5>
                      </div>
                      <div class="card-body text-center">
                        <div id="divImgRel" class="mb-3">
                          <img src="../img/logo_rel.jpg" class="img-thumbnail" style="max-height: 100px;"
                            id="target-rel">
                        </div>
                        <div class="custom-file">
                          <input type="file" class="custom-file-input" name="imgRel" onChange="carregarImgRel();"
                            id="foto-rel">
                          <label class="custom-file-label" for="foto-rel">Escolher
                            arquivo</label>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="card mb-4">
                      <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fa fa-qrcode mr-2"></i>QRCode (*jpg)</h5>
                      </div>
                      <div class="card-body text-center">
                        <div id="divImgQRCode" class="mb-3">
                          <img src="../img/qrcode.jpg" class="img-thumbnail" style="max-height: 100px;"
                            id="target-QRCode">
                        </div>
                        <small class="text-muted d-block mb-2">Min 200x200 pixels</small>
                        <div class="custom-file">
                          <input type="file" class="custom-file-input" name="imgQRCode" onChange="carregarImgQRCode();"
                            id="foto-QRCode">
                          <label class="custom-file-label" for="foto-QRCode">Escolher
                            arquivo</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab 2: Valores e Porcentagens -->
            <div class="tab-pane fade" id="numeric" role="tabpanel" aria-labelledby="numeric-tab">

              <div class="card-body">
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-list mr-1"></i> Itens Paginação</label>
                      <input type="number" class="form-control" id="itens_pag" name="itens_pag"
                        value="<?php echo $itens_pag ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-th mr-1"></i> Itens Relacionados</label>
                      <input type="number" class="form-control" id="itens_rel" name="itens_rel"
                        value="<?php echo $itens_rel ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-unlock mr-1"></i> Aulas Disponíveis</label>
                      <input type="number" class="form-control" id="aulas_lib" name="aulas_lib"
                        value="<?php echo $aulas_lib ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-star mr-1"></i> Cartões Fidelidade</label>
                      <input type="number" class="form-control" id="cartoes_fidelidade" name="cartoes_fidelidade"
                        value="<?php echo $cartoes_fidelidade ?>">
                    </div>
                  </div>
                </div>

                <h5 class="border-bottom pb-2 mb-3 mt-4">Configurações de Pagamento</h5>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Desconto Pix %</label>
                      <input type="number" class="form-control" id="desconto_pix" name="desconto_pix"
                        value="<?php echo $desconto_pix ?>">
                    </div>
                  </div>

                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Acréscimo Cartão %</label>
                      <input type="number" class="form-control" id="acrescimo_cartao_credito" name="acrescimo_cartao_credito"
                        value="<?php echo $acrescimo_cartao_credito ?>">
                    </div>
                  </div>

                  
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-dollar-sign mr-1"></i> Taxa Boleto R$</label>
                      <input type="text" class="form-control" id="taxa_boleto" name="taxa_boleto"
                        value="<?php echo $taxa_boleto ?>">
                    </div>
                  </div>
                </div>

                <h5 class="border-bottom pb-2 mb-3 mt-4">Configurações de Comissões</h5>
                <div class="row">
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Professor %</label>
                      <input type="number" class="form-control" id="comissao_professor" name="comissao_professor"
                        value="<?php echo $comissao_professor ?>">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Tesoureiro %</label>
                      <input type="number" class="form-control" id="comissao_tesoureiro" name="comissao_tesoureiro"
                        value="<?php echo $comissao_tesoureiro ?>">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Secretário %</label>
                      <input type="number" class="form-control" id="comissao_secretario" name="comissao_secretario"
                        value="<?php echo $comissao_secretario ?>">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Tutor %</label>
                      <input type="number" class="form-control" id="comissao_tutor" name="comissao_tutor"
                        value="<?php echo $comissao_tutor ?>">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Parceiro %</label>
                      <input type="number" class="form-control" id="comissao_parceiro" name="comissao_parceiro"
                        value="<?php echo $comissao_parceiro ?>">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Assessor %</label>
                      <input type="number" class="form-control" id="comissao_assessor" name="comissao_assessor"
                        value="<?php echo $comissao_assessor ?>">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Vendedor %</label>
                      <input type="number" class="form-control" id="comissao_vendedor" name="comissao_vendedor"
                        value="<?php echo $comissao_vendedor ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-calendar-day mr-1"></i> Dia PGTO Comissão</label>
                      <input type="number" class="form-control" id="dia_pgto_comissao" name="dia_pgto_comissao"
                        value="<?php echo $dia_pgto_comissao ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-dollar-sign mr-1"></i> R$ Valor Max Cartão</label>
                      <input type="text" class="form-control" id="valor_max_cartao" name="valor_max_cartao"
                        value="<?php echo $valor_max_cartao ?>">
                    </div>
                  </div>
                </div>

                <h5 class="border-bottom pb-2 mb-3 mt-4">Configurações de Email e Matrículas</h5>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-envelope-open mr-1"></i> Total Emails/Envio</label>
                      <input type="number" class="form-control" id="total_emails_por_envio"
                        name="total_emails_por_envio" value="<?php echo $total_emails_por_envio ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-clock mr-1"></i> Intervalo Envio (min)</label>
                      <input type="number" class="form-control" id="intervalo_envio_email" name="intervalo_envio_email"
                        value="<?php echo $intervalo_envio_email ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-calendar-alt mr-1"></i> Dias Email Matrícula</label>
                      <input type="number" class="form-control" id="dias_email_matricula" name="dias_email_matricula"
                        value="<?php echo $dias_email_matricula ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-trash-alt mr-1"></i> Dias Excluir Matrícula</label>
                      <input type="number" class="form-control" id="dias_excluir_matricula"
                        name="dias_excluir_matricula" value="<?php echo $dias_excluir_matricula ?>">
                    </div>
                  </div>
                </div>

                <h5 class="border-bottom pb-2 mb-3 mt-4">Configurações Acadêmicas</h5>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-question-circle mr-1"></i> Questionário</label>
                      <select class="form-control" name="questionario" id="questionario_config"
                        value="<?php echo $questionario_config ?>">
                        <option value="Sim" <?php if ($questionario_config == 'Sim') { ?> selected <?php } ?>>Sim</option>
                        <option value="Não" <?php if ($questionario_config == 'Não') { ?> selected <?php } ?>>Não</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-percentage mr-1"></i> Média Aprovação %</label>
                      <input type="number" class="form-control" id="media_config" name="media"
                        value="<?php echo $media_config ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-award mr-1"></i> Verso Certificado</label>
                      <select class="form-control" name="verso" id="verso" value="<?php echo $verso ?>">
                        <option value="Sim" <?php if ($verso == 'Sim') { ?> selected <?php } ?>>
                          Sim</option>
                        <option value="Não" <?php if ($verso == 'Não') { ?> selected <?php } ?>>
                          Não</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-envelope mr-1"></i> Email ADM Matriculas</label>
                      <select class="form-control" name="email_adm_mat" id="email_adm_mat"
                        value="<?php echo $email_adm_mat ?>">
                        <option value="Sim" <?php if ($email_adm_mat == 'Sim') { ?> selected <?php } ?>>Sim</option>
                        <option value="Não" <?php if ($email_adm_mat == 'Não') { ?> selected <?php } ?>>Não</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab 1: Dados Básicos -->
            <div class="tab-pane fade  " id="basic" role="tabpanel" aria-labelledby="basic-tab">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fa fa-building mr-1"></i> Nome Sistema</label>
                      <input type="text" class="form-control" name="nome_sistema" value="<?php echo $nome_sistema ?>"
                        required>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fa fa-phone mr-1"></i> Telefone Sistema</label>
                      <input type="text" class="form-control" id="tel_sistema" name="tel_sistema"
                        value="<?php echo $tel_sistema ?>" required>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fa fa-envelope mr-1"></i> Email Sistema</label>
                      <input type="text" class="form-control" id="email_sistema" name="email_sistema"
                        value="<?php echo $email_sistema ?>" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fa fa-id-card mr-1"></i> CNPJ Sistema</label>
                      <input type="text" class="form-control" id="cnpj_sistema" name="cnpj_sistema"
                        value="<?php echo $cnpj_sistema ?>">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fa fa-qrcode mr-1"></i> Tipo Chave Pix</label>
                      <select class="form-control" name="tipo_chave_pix_sistema" id="tipo_chave_pix_sistema"
                        value="<?php echo $tipo_chave_pix ?>">
                        <option value="CNPJ" <?php if ($tipo_chave_pix == 'CNPJ') { ?> selected <?php } ?>>CNPJ</option>
                        <option value="CPF" <?php if ($tipo_chave_pix == 'CPF') { ?> selected <?php } ?>>CPF</option>
                        <option value="E-mail" <?php if ($tipo_chave_pix == 'E-mail') { ?> selected <?php } ?>>E-mail
                        </option>
                        <option value="Telefone" <?php if ($tipo_chave_pix == 'Telefone') { ?> selected <?php } ?>>
                          Telefone</option>
                        <option value="Código" <?php if ($tipo_chave_pix == 'Código') { ?> selected <?php } ?>>Código
                        </option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fa fa-key mr-1"></i> Chave Pix</label>
                      <input type="text" class="form-control" id="chave_pix" name="chave_pix"
                        value="<?php echo $chave_pix ?>">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fa fa-facebook mr-1" style="color: blue;"></i>
                        Facebook</label>
                      <input type="text" class="form-control" id="facebook_sistema" name="facebook_sistema"
                        value="<?php echo $facebook_sistema ?>">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fa fa-instagram mr-1" style="color: #cc2366;"></i>
                        Instagram</label>
                      <input type="text" class="form-control" id="instagram_sistema" name="instagram_sistema"
                        value="<?php echo $instagram_sistema ?>">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fa fa-youtube mr-1" style="color: red;"></i>
                        Youtube</label>
                      <input type="text" class="form-control" id="youtube_sistema" name="youtube_sistema"
                        value="<?php echo $youtube_sistema ?>">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fa fa-film mr-1"></i> Url Vídeo Página Sobre</label>
                      <input type="text" class="form-control" id="video_sobre" name="video_sobre"
                        value="<?php echo $video_sobre ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-graduation-cap mr-1"></i> Professor se
                        Cadastrar</label>
                      <select class="form-control" name="professor_cad" id="professor_cad"
                        value="<?php echo $professor_cad ?>">
                        <option value="Não" <?php if ($professor_cad == 'Não') { ?> selected <?php } ?>>Não</option>
                        <option value="Sim" <?php if ($professor_cad == 'Sim') { ?> selected <?php } ?>>Sim</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><i class="fa fa-credit-card mr-1"></i> Api Cartão</label>
                      <select class="form-control" name="api_cartao" id="api_cartao" value="<?php echo $api_cartao ?>">
                        <option value="Api" <?php if ($api_cartao == 'Api') { ?> selected <?php } ?>>Api Site (Seguro)
                        </option>
                        <option value="Direta" <?php if ($api_cartao == 'Direta') { ?> selected <?php } ?>>Api
                          Transparente</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab 0: Informações -->
            <div class="tab-pane fade" id="config" role="tabpanel" aria-labelledby="config-tab">
              <div class="card-body">

                <div
                  style="padding: 10px; margin-top: 22px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #4A4A4A;">
                  <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 12px; color: #2C3E50;">
                    Informações do Sistema
                  </h2>
                  <p style="font-size: 15px; margin-bottom: 24px; line-height: 1.6;">
                    Personalize e ajuste as principais configurações do seu sistema. Utilize as abas
                    acima para navegar
                    entre as categorias disponíveis:
                  </p>

                  <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
                    <div style="background: #F7F9FA; border: 1px solid #E1E8ED; border-radius: 8px; padding: 16px;">
                      <strong style="display: block; font-size: 16px; margin-bottom: 6px;">🛠
                        Dados Básicos</strong>
                      <span style="font-size: 14px; color: #6B7C93;">Configure informações gerais
                        essenciais para o
                        funcionamento do sistema.</span>
                    </div>
                    <div style="background: #F7F9FA; border: 1px solid #E1E8ED; border-radius: 8px; padding: 16px;">
                      <strong style="display: block; font-size: 16px; margin-bottom: 6px;">📈
                        Valores e
                        Porcentagens</strong>
                      <span style="font-size: 14px; color: #6B7C93;">Defina taxas, comissões de
                        venda e outros
                        percentuais.</span>
                    </div>
                    <div style="background: #F7F9FA; border: 1px solid #E1E8ED; border-radius: 8px; padding: 16px;">
                      <strong style="display: block; font-size: 16px; margin-bottom: 6px;">📤
                        Uploads</strong>
                      <span style="font-size: 14px; color: #6B7C93;">Gerencie parâmetros para
                        envio e armazenamento seguro
                        de arquivos.</span>
                    </div>
                    <div style="background: #F7F9FA; border: 1px solid #E1E8ED; border-radius: 8px; padding: 16px;">
                      <strong style="display: block; font-size: 16px; margin-bottom: 6px;">🔒
                        Segurança</strong>
                      <span style="font-size: 14px; color: #6B7C93;">Ajuste regras de segurança
                        para proteger a aplicação
                        e os dados.</span>
                    </div>
                  </div>

                  <p style="font-size: 14px; color: #7B8A99;">
                    Lembre-se de revisar cuidadosamente e salvar as alterações após finalizar a
                    configuração.
                  </p>
                </div>

              </div>
            </div>
          </div>



        </div>

        <div id="configTutorial" class="card-body ">
          <div style="padding: 24px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #4A4A4A;">
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 12px; color: #2C3E50;">Informações
              do Sistema
            </h2>
            <p style="font-size: 15px; margin-bottom: 24px; line-height: 1.6;">
              Personalize e ajuste as principais configurações do seu sistema. Utilize as abas acima para
              navegar
              entre as categorias disponíveis:
            </p>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
              <div style="background: #F7F9FA; border: 1px solid #E1E8ED; border-radius: 8px; padding: 16px;">
                <strong style="display: block; font-size: 16px; margin-bottom: 6px;">🛠 Dados
                  Básicos</strong>
                <span style="font-size: 14px; color: #6B7C93;">Configure informações gerais essenciais
                  para o
                  funcionamento do sistema.</span>
              </div>
              <div style="background: #F7F9FA; border: 1px solid #E1E8ED; border-radius: 8px; padding: 16px;">
                <strong style="display: block; font-size: 16px; margin-bottom: 6px;">📈 Valores e
                  Porcentagens</strong>
                <span style="font-size: 14px; color: #6B7C93;">Defina taxas, comissões de venda e outros
                  percentuais.</span>
              </div>
              <div style="background: #F7F9FA; border: 1px solid #E1E8ED; border-radius: 8px; padding: 16px;">
                <strong style="display: block; font-size: 16px; margin-bottom: 6px;">📤 Uploads</strong>
                <span style="font-size: 14px; color: #6B7C93;">Gerencie parâmetros para envio e
                  armazenamento seguro
                  de arquivos.</span>
              </div>
              <div style="background: #F7F9FA; border: 1px solid #E1E8ED; border-radius: 8px; padding: 16px;">
                <strong style="display: block; font-size: 16px; margin-bottom: 6px;">🔒
                  Segurança</strong>
                <span style="font-size: 14px; color: #6B7C93;">Ajuste regras de segurança para proteger
                  a aplicação
                  e os dados.</span>
              </div>
            </div>

            <p style="font-size: 14px; color: #7B8A99;">
              Lembre-se de revisar cuidadosamente e salvar as alterações após finalizar a configuração.
            </p>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>Salvar
            Alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>





<!-- Modal Rel Vendas -->
<div class="modal fade" id="RelVen" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="exampleModalLabel">Relatório de Vendas
          <small>(
            <a href="#" onclick="datas('1980-01-01', 'tudo-Ven', 'Ven')">
              <span style="color:#000" id="tudo-Ven">Tudo</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Ven', 'Ven')">
              <span id="hoje-Ven">Hoje</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Ven', 'Ven')">
              <span style="color:#000" id="mes-Ven">Mês</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Ven', 'Ven')">
              <span style="color:#000" id="ano-Ven">Ano</span>
            </a>
            )</small>



        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="../rel/vendas_class.php" target="_blank">
        <div class="modal-body">

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Data Inicial</label>
                <input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Ven"
                  value="<?php echo date('Y-m-d') ?>" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Data Final</label>
                <input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Ven"
                  value="<?php echo date('Y-m-d') ?>" required>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Forma de PGTO</label>
                <select class="form-control sel13" name="pago" style="width:100%;">
                  <option value="">Todas</option>
                  <option value="Pix">Pix</option>
                  <option value="MP">MP</option>
                  <option value="Boleto">Boleto</option>
                  <option value="Paypal">Paypal</option>
                </select>
              </div>
            </div>

          </div>




        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Gerar Relatório</button>
        </div>
      </form>

    </div>
  </div>
</div>










<!-- Modal Rel Contas -->
<div class="modal fade" id="RelCon" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="exampleModalLabel">Relatório de Contas
          <small>(
            <a href="#" onclick="datas('1980-01-01', 'tudo-Con', 'Con')">
              <span style="color:#000" id="tudo-Con">Tudo</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Con', 'Con')">
              <span id="hoje-Con">Hoje</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Con', 'Con')">
              <span style="color:#000" id="mes-Con">Mês</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Con', 'Con')">
              <span style="color:#000" id="ano-Con">Ano</span>
            </a>
            )</small>



        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="../rel/contas_class.php" target="_blank">
        <div class="modal-body">

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Data Inicial</label>
                <input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Con"
                  value="<?php echo date('Y-m-d') ?>" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Data Final</label>
                <input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Con"
                  value="<?php echo date('Y-m-d') ?>" required>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Pago</label>
                <select class="form-control sel13" name="pago" style="width:100%;">
                  <option value="">Todas</option>
                  <option value="Sim">Somente Pagas</option>
                  <option value="Não">Pendentes</option>

                </select>
              </div>
            </div>

          </div>



          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Pagar / Receber</label>
                <select class="form-control sel13" name="tabela" style="width:100%;">
                  <option value="pagar">Contas à Pagar</option>
                  <option value="receber">Contas à Receber</option>

                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Consultar Por</label>
                <select class="form-control sel13" name="busca" style="width:100%;">
                  <option value="vencimento">Data de Vencimento</option>
                  <option value="data_pgto">Data de Pagamento</option>

                </select>
              </div>
            </div>



          </div>




        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Gerar Relatório</button>
        </div>
      </form>

    </div>
  </div>
</div>








<!-- Modal Rel Lucro -->
<div class="modal fade" id="RelLucro" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="exampleModalLabel">Relatório de Lucro
          <small>(
            <a href="#" onclick="datas('1980-01-01', 'tudo-Luc', 'Luc')">
              <span style="color:#000" id="tudo-Luc">Tudo</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Luc', 'Luc')">
              <span id="hoje-Luc">Hoje</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Luc', 'Luc')">
              <span style="color:#000" id="mes-Luc">Mês</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Luc', 'Luc')">
              <span style="color:#000" id="ano-Luc">Ano</span>
            </a>
            )</small>



        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="../rel/lucro_class.php" target="_blank">
        <div class="modal-body">

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Data Inicial</label>
                <input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Luc"
                  value="<?php echo date('Y-m-d') ?>" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Data Final</label>
                <input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Luc"
                  value="<?php echo date('Y-m-d') ?>" required>
              </div>
            </div>


          </div>




        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Gerar Relatório</button>
        </div>
      </form>

    </div>
  </div>
</div>







<!-- Modal Rel Comissoes -->
<div class="modal fade" id="RelComissoes" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="exampleModalLabel">Relatório de Comissões
          <small>(
            <a href="#" onclick="datas('1980-01-01', 'tudo-Com', 'Com')">
              <span style="color:#000" id="tudo-Com">Tudo</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Com', 'Com')">
              <span id="hoje-Com">Hoje</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Com', 'Com')">
              <span style="color:#000" id="mes-Com">Mês</span>
            </a> /
            <a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Com', 'Com')">
              <span style="color:#000" id="ano-Com">Ano</span>
            </a>
            )</small>



        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="../rel/comissoes_class.php" target="_blank">
        <div class="modal-body">

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Data Inicial</label>
                <input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Com"
                  value="<?php echo date('Y-m-d') ?>" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Data Final</label>
                <input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Com"
                  value="<?php echo date('Y-m-d') ?>" required>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Pago</label>
                <select class="form-control sel13" name="pago" style="width:100%;">
                  <option value="">Todas</option>
                  <option value="Sim">Somente Pagas</option>
                  <option value="Não">Pendentes</option>

                </select>
              </div>
            </div>

          </div>



          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Colaborador</label>
                <select class="form-control sel82" name="sel_professor" id="sel_professor" style="width:100%;">
                  <option value="">Selecione um Colaborador</option>
                  <?php
                  $query = $pdo->query("SELECT * FROM usuarios where nivel = 'Professor' or nivel = 'Administrador' or nivel = 'Secretario' or nivel = 'Parceiro' or nivel = 'Tesoureiro' or nivel = 'Tutor' or nivel = 'Assessor' or nivel = 'Vendedor'order by nome asc");
                  $res = $query->fetchAll(PDO::FETCH_ASSOC);
                  for ($i = 0; $i < @count($res); $i++) {
                    foreach ($res[$i] as $key => $value) {
                    }

                    ?>
                    <option value="<?php echo $res[$i]['id'] ?>">(<?php echo $res[$i]['nivel'] ?>)
                      <?php echo $res[$i]['nome'] ?>
                    </option>

                  <?php } ?>

                </select>
              </div>
            </div>




          </div>




        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Gerar Relatório</button>
        </div>
      </form>

    </div>
  </div>
</div>






<link rel="stylesheet" type="text/css" href="../DataTables/datatables.min.css" />
<script type="text/javascript" src="../DataTables/datatables.min.js"></script>


<script type="text/javascript">
  $("#form-usu").submit(function () {
    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
      url: "editar-perfil.php",
      type: 'POST',
      data: formData,

      success: function (mensagem) {
        $('#mensagem-usu').text('');
        $('#mensagem-usu').removeClass()
        if (mensagem.trim() == "Editado com Sucesso") {
          location.reload();
          //$('#btn-fechar-usu').click();						

        } else {

          $('#mensagem-usu').addClass('text-danger')
          $('#mensagem-usu').text(mensagem)
        }


      },

      cache: false,
      contentType: false,
      processData: false,

    });

  });
</script>




<script type="text/javascript">
  $("#form-config").submit(function () {
    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
      url: "editar-config.php",
      type: 'POST',
      data: formData,

      success: function (mensagem) {

        $('#mensagem-config').text('');
        $('#mensagem-config').removeClass()
        if (mensagem.trim() == "Editado com Sucesso") {
          location.reload();
          //$('#btn-fechar-usu').click();						

        } else {

          $('#mensagem-config').addClass('text-danger')
          $('#mensagem-config').text(mensagem)
        }


      },

      cache: false,
      contentType: false,
      processData: false,

    });

  });
</script>




<script type="text/javascript">
  function carregarImgPerfil() {
    var target = document.getElementById('target-usu');
    var file = document.querySelector("#foto-usu").files[0];

    var reader = new FileReader();

    reader.onloadend = function () {
      target.src = reader.result;
    };

    if (file) {
      reader.readAsDataURL(file);

    } else {
      target.src = "";
    }
  }
</script>




<script type="text/javascript">
  function carregarImgLogo() {
    var target = document.getElementById('target-logo');
    var file = document.querySelector("#foto-logo").files[0];

    var reader = new FileReader();

    reader.onloadend = function () {
      target.src = reader.result;
    };

    if (file) {
      reader.readAsDataURL(file);

    } else {
      target.src = "";
    }
  }
</script>




<script type="text/javascript">
  function carregarImgFavicon() {
    var target = document.getElementById('target-favicon');
    var file = document.querySelector("#foto-favicon").files[0];

    var reader = new FileReader();

    reader.onloadend = function () {
      target.src = reader.result;
    };

    if (file) {
      reader.readAsDataURL(file);

    } else {
      target.src = "";
    }
  }
</script>



<script type="text/javascript">
  function carregarImgRel() {
    var target = document.getElementById('target-rel');
    var file = document.querySelector("#foto-rel").files[0];

    var reader = new FileReader();

    reader.onloadend = function () {
      target.src = reader.result;
    };

    if (file) {
      reader.readAsDataURL(file);

    } else {
      target.src = "";
    }
  }
</script>



<script type="text/javascript">
  function carregarImgQRCode() {
    var target = document.getElementById('target-QRCode');
    var file = document.querySelector("#foto-QRCode").files[0];

    var reader = new FileReader();

    reader.onloadend = function () {
      target.src = reader.result;
    };

    if (file) {
      reader.readAsDataURL(file);

    } else {
      target.src = "";
    }
  }
</script>




<!-- Mascaras JS -->
<script type="text/javascript" src="../js/mascaras.js"></script>
<!-- Ajax para funcionar Mascaras JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script>


<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>



<style type="text/css">
  .select2-selection__rendered {
    line-height: 36px !important;
    font-size: 16px !important;
    color: #666666 !important;

  }

  .select2-selection {
    height: 36px !important;
    font-size: 16px !important;
    color: #666666 !important;

  }
</style>





<script type="text/javascript">
  function datas(data, id, campo) {

    var data_atual = "<?= $data_atual ?>";
    var separarData = data_atual.split("-");
    var mes = separarData[1];
    var ano = separarData[0];

    var separarId = id.split("-");

    if (separarId[0] == 'tudo') {
      data_atual = '2100-12-31';
    }

    if (separarId[0] == 'ano') {
      data_atual = ano + '-12-31';
    }

    if (separarId[0] == 'mes') {
      if (mes == 1 || mes == 3 || mes == 5 || mes == 7 || mes == 8 || mes == 10 || mes == 12) {
        data_atual = ano + '-' + mes + '-31';
      } else if (mes == 4 || mes == 6 || mes == 9 || mes == 11) {
        data_atual = ano + '-' + mes + '-30';
      } else {
        data_atual = ano + '-' + mes + '-28';
      }

    }

    $('#dataInicialRel-' + campo).val(data);
    $('#dataFinalRel-' + campo).val(data_atual);

    document.getElementById('hoje-' + campo).style.color = "#000";
    document.getElementById('mes-' + campo).style.color = "#000";
    document.getElementById(id).style.color = "blue";
    document.getElementById('tudo-' + campo).style.color = "#000";
    document.getElementById('ano-' + campo).style.color = "#000";
    document.getElementById(id).style.color = "blue";
  }
</script>



<script type="text/javascript">
  $(document).ready(function () {
    $('.sel82').select2({
      dropdownParent: $('#RelComissoes')
    });
  });
</script>


<?php
function isActiveMenu($href)
{
  $currentUrl = $_SERVER['REQUEST_URI'];

  // Remove domínio e parâmetros extras da URL atual
  $currentPath = parse_url($currentUrl, PHP_URL_PATH);
  $currentPage = basename($currentPath); // ex: index.php
  $currentQuery = $_SERVER['QUERY_STRING']; // ex: pagina=matriculas

  // Checa se href é exatamente igual a URI
  if ($href === $currentPage || strpos($currentUrl, $href) !== false) {
    return 'active';
  }

  // Checa se href tem query string (ex: index.php?pagina=matriculas)
  if (!empty($currentQuery) && strpos($href, $currentQuery) !== false) {
    return 'active';
  }

  return '';
}
?>


<script>
  document.getElementById('add-cor').addEventListener('click', function () {
    const template = document.querySelector('#cor-template').innerHTML;
    const container = document.querySelector('#cores-container');
    container.insertAdjacentHTML('beforeend', template);
  });

  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-cor')) {
      e.target.closest('.cor-item').remove();
    }
  });
</script>


<script>
  document.getElementById('form-cores').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('editar-cores.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        document.getElementById('mensagem-cores').innerHTML = `
        <div class="alert alert-${data.status}">${data.mensagem}</div>
      `;
        if (data.status === 'success') {
          setTimeout(() => {
            location.reload();
          }, 1500);
        }
      })
      .catch(() => {
        document.getElementById('mensagem-cores').innerHTML = `
        <div class="alert alert-danger">Erro ao salvar as cores.</div>
      `;
      });
  });
</script>
