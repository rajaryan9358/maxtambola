<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets\logo.ico">
    <title>Max Tambola</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="{{ URL::asset('css/home.css') }} ">

        <script src="https://kit.fontawesome.com/0e0798da3d.js" crossorigin="anonymous"></script>
    <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>
    <style>
        body {
            font-family: 'Poppins';
        }
    </style>
</head>
<body>
<section id="navbar" style="background-color: white;z-index: 99;">
        <!-- navigation  bar -->
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light " style="padding-top:20px">
                <a class="navbar-brand" href="#"><span style="color:#D34FE2">MAX </span><span>TAMBOLA</span></a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo01"
                    aria-controls="navbarTogglerDemo01" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarTogglerDemo01">
                    <ul class="navbar-nav">
                        <li class="nav-item active">
                            <a class="nav-link" href="#home">Home</a>
                        </li>
                        <li class="nav-item active">
                            <a class="nav-link" href="./about">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#download">Download</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#play">How to Play</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#how-to-download">How to download</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="./contact">Contact Us</a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </section>

    <div class="container" id="home">

        <!-- home page -->
        <div class="row ">
            <div class="col-lg-6 homepage">
                <div class="hero__caption">
                    <h1 data-animation="fadeInUp" data-delay=".6s" class="" style="animation-delay: 0.6s;">Play Fun
                        Earn<br>with Max Tambola</h1>
                    <p data-animation="fadeInUp" data-delay=".8s" class="" style="animation-delay: 0.8s;">Play Housie
                        game and win REAL cash prizes 24x7. Withdraw your cash instantly to any of your bank account.
                        The fun game of probability with exceptional features.
                        The game of numbers that does not require any
                        mathematical computations – just quick fingers!
                    </p>
                    <!-- Slider btn -->
                    <div class="slider-btns">
                        <!-- Hero-btn -->
                        <a data-animation="fadeInLeft" data-delay="1.0s" target="_blank"
                            href="/app/max_tambola.apk" download="max_tambola.apk" class="btn radius-btn"
                            tabindex="0" style="animation-delay: 1s;">Download</a>
                        <!-- Video Btn -->

                        <a href="https://www.youtube.com/watch?v=0kz7Bhzr1Sk" target="_blank" class="yt-btn btn">
                                <i class="fa-brands fa-youtube yt-icon"></i>
                                <p class="yt-text">Watch On Youtube</p>
                            
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero__img d-none d-lg-block f-right" data-animation="fadeInRight" data-delay="1s"
                    style="animation-delay: 1s;">
                    <img src="assets\mobile.png" alt="">
                </div>
            </div>
            <!-- <div class="col"></div> -->
        </div>


    </div>
    <section id="download">
        <div class="available-app-area">
            <div class="container">
                <div class="row d-flex justify-content-between">
                    <div class="col-xl-5 col-lg-6">
                        <div class="app-caption">
                            <div class="section-tittle section-tittle3">
                                <h2>Our App Available For Android Device Download now</h2>
                                <p>Download from Google Play Store and play now to earn</p>
                                <div class="app-btn">
                                    <!--<a href="#" class="app-btn1"><img src="assets/img/shape/app_btn1.png" alt=""></a>-->
                                    <a href="/app/max_tambola.apk" download="max_tambola.apk"  class="app-btn2"><img src="{{ URL::asset('assets/android-download.png') }}"
                                            alt=""></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6">
                        <div class="app-img">
                            <img src="{{ URL::asset('assets/tambola.png') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <!-- Shape -->
            <div class="app-shape">
                <img src="{{ URL::asset('assets/app-shape-top.png') }}" alt="" class="app-shape-top heartbeat d-none d-lg-block">
                <img src="{{ URL::asset('assets/app-shape-left.png') }}" alt="" class="app-shape-left d-none d-xl-block">
                <!-- <img src="assets/img/shape/app-shape-right.png" alt="" class="app-shape-right bounce-animate "> -->
            </div>
        </div>
    </section>

    <div style="margin-bottom: 8vw; "id="play"></div>
    <section>
        <div class="heading"  id="prize">How to play</div>
        <div class="whole-wrap">
            <div class="container box_1170">
                <div class="section-top-border">
                    <div class="row">
                        <div class="col-md-9 mt-sm-20">
                            <p>
                                There are 90 numbers in Housie Book Game and each ticket has 15 numbers. So, 6 tickets
                                has 15*6 = 90 numbers.<br>
                                In all these 6 tickets, each number from 1-90 occurs once only (Not applied in shuffled
                                tickets) so covering all 90 numbers.<br>
                                Number will be present in only one ticket from the set of 6 tickets when system picked a
                                number.
                            </p>
                            <p>
                            </p>
                            <ul class="unordered-list">
                                <li>Total available numbers in Housie Game is 90 which are 1-90</li>
                                <li>We can generate 6 tickets from the 90 numbers and these tickets shared in
                                    sequentials order to player when they are purchasing (Unless selected for shuffled
                                    tickets)</li>
                                <li>Each Housie ticket contains 3 rows and 9 columns</li>
                                <li>Each ticket contains 15 numbers</li>
                                <li>Each row in a ticket contains 5 numbers</li>
                                <li>Each columns may contains 1,2 or 3 numbers.</li>
                                <li>Column 1 contains any numbers between 1-9</li>
                                <li>Column 2 contains any numbers between 10-19</li>
                                <li>Column 3 contains any numbers between 20-29</li>
                                <li>Column 4 contains any numbers between 30-39</li>
                                <li>Column 5 contains any numbers between 40-49</li>
                                <li>Column 6 contains any numbers between 50-59</li>
                                <li>Column 7 contains any numbers between 60-69</li>
                                <li>Column 8 contains any numbers between 70-79</li>
                                <li>Column 9 contains any numbers between 80-90</li>
                            </ul>
                            <p></p>
                        </div>
                        <div class="col-md-3">
                            <img src="{{ URL::asset('assets/coinboard.png') }}" alt="Coins Board" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section class="container">
        <div class="heading" style="margin-top: 0vw;" id="prize">Prizes</div>
        <div class="row">
            <div class="col col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <img src="{{ URL::asset('assets/top_line.png') }}" class="prize-img">
            </div>
            <div class="col col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <img src="{{ URL::asset('assets/middle_line.png') }}" class="prize-img">
            </div>
            <div class="col col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <img src="{{ URL::asset('assets/bottom_line.png') }}" class="prize-img">
            </div>
            <div class="col col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <img src="{{ URL::asset('assets/corners.png') }}" class="prize-img">
            </div>
            <div class="col col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <img src="{{ URL::asset('assets/early_five.png') }}" class="prize-img">
            </div>
            <div class="col col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <img src="{{ URL::asset('assets/full_house.png') }}" class="prize-img">
            </div>

        </div>


        </div>
    </section>
    



    <div style="margin-bottom: 8vw; "id="how-to-download"></div>
    <section class="container">

        <h2 style="margin-bottom: 3vw;" >Download <span style="color: #D34FE2;">Maxtambola</span> App for Android</h2>
        <div>
            <p>
                After downloading the APK file, you might get a warning message on your screen. Just click on the “Install Anyway” option to complete installation.

Whenever you download an APK file from a 3rd party, the Android device will display a default warning message for protection. Since you are `downloading the file from Maxtambola.com (official website), it is 100% safe and free from any viruses. You don’t need to worry at all.
            </p>

            <img src="{{ URL::asset('assets/safe_secure.png') }}" style="display: block;margin: auto; width: 237px;margin-bottom: 5vw;">
        </div>
        <div class="row">
            <div class="col col-12 col-lg-4 col-md-4 col-sm-12">
                <img src="{{ URL::asset('assets/install_app1.png') }}" class="install-img">
            </div>
            <div class="col col-12 col-lg-4 col-md-4 col-sm-12">
                <img src="{{ URL::asset('assets/install_app2.png') }}" class="install-img">
            </div><div class="col col-12 col-lg-4 col-md-4 col-sm-12 ">
                <img src="{{ URL::asset('assets/install_app3.png') }}" class="install-img">
            </div>
            
        </div>
    </section>


    <footer>
        <div class="footer-top">
          <img src="{{ URL::asset('assets/logo.png') }}" alt="">
          <h2> MAXTAMBOLA</h2>
        </div>
        <div class="icons">
          <div>
            <a href=""><i class="fab fa-twitter"></i></a>
          </div>
          <div>
            <a href=""><i class="fab fa-instagram"></i></a>
          </div>
          <div>
            <a href=""><i class="fab fa-linkedin-in"></i></a>
          </div>
          <div>
    
            <a href=""><i class="fab fa-youtube"></i></a>
          </div>
          <div>
            <a href=""><i class="fab fa-facebook-f"></i></a>
          </div>
        </div>
        <div class="footer-content">
    
          <ul>
            <li><a href="#">DOWNLOAD APP</li>
            <li><a href="#download">HOW TO DOWNLOAD</a></li>
            <li><a href="#play">HOW TO PLAY</a></li>
            <li><a href="faqs">FAQs</a></li>
            <li><a href="privacy">PRIVACY POLICY</a></li>
            <li><a href="terms">TERMS AND CONDITION</a></li>
           </ul>
    
        </div>
      </footer>



    <script src="https://kit.fontawesome.com/7ea0deeaa5.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
        crossorigin="anonymous"></script>

    <script>
        // When the user scrolls the page, execute myFunction
        window.onscroll = function () { myFunction() };

        // Get the navbar
        var navbar = document.getElementById("navbar");

        // Get the offset position of the navbar
        var sticky = navbar.offsetTop + 20;

        // Add the sticky class to the navbar when you reach its scroll position. Remove "sticky" when you leave the scroll position
        function myFunction() {
            if (window.pageYOffset >= sticky) {
                navbar.classList.add("sticky")
            } else {
                navbar.classList.remove("sticky");
            }
        }


    </script>
</body>
</html>