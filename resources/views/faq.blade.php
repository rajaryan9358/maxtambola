<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="shortcut icon" href="assets\logo.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ</title>
    <link rel="stylesheet" href="{{ URL::asset('css/home.css') }} ">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
        crossorigin="anonymous"></script>
        <script src="https://kit.fontawesome.com/0e0798da3d.js" crossorigin="anonymous"></script>

    <style>
        .ques {
            font-weight: bold;
            padding-left: 20 px;
            padding-top: 5 px;
            padding-bottom: 0 px;
        }

        .ans {
            padding-left: 30 px;
            padding-top: 2 px;
            padding-bottom: 7 px;
        }

        * {
            box-sizing: border-box;
        }

        .heading {
            color: orange;
        }

        .heading {
            font-weight: bold;
            padding-left: 5 px;
            padding-top: 10 px;
            padding-bottom: 5 px;
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
                            <a class="nav-link" href="./">Home</a>
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

    <div class="container">
        <!-- <h2 style="margin: auto; text-align: center;">Frequently Asked Question</h2><br> -->
        <div class="heading">General Question</div>
        <div class="ques">What is MaxTambola about? </div>
        <div class="ans">MaxTambola is an online entertainment gaming provider which is delivered to play tambola also
            known as Housie. We are very much passionated about this game and we built it for our circle initially and
            we spreaded our wings to public in 2021. We are successfully running in the market and also very transparent
            to the customer by providing vast services/options to the players. We always belive good support always
            turns to good customer and we proved that.</div><br>

        <div class="ques">What are the different prizes?</div>
        <div class="ans">The caller / dealer calls out randomly generated number/ cues one at a time. Players are
            required to check their tickets and mark off the numbers if applicable. The person who gets to mark off all
            the numbers in the winning combination first is the winner. You can choose to play with a maximum of three
            tickets with players not exceeding 25 per game. You have a chance to win from the following winning
            combinations:

            <ol>
                <li> Early Five</li>
                <li>Four Corners</li>
                <li>Top line, Middle line and bottom line (each horizontal line gets a separate prize)</li>
                <li>Full House</li>
            </ol>

        </div><br>

        <div class="ques">How many numbers are there in a Tambola game?</div>
        <div class="ans">In classic tambola there are 90 numbers. People often get confused with 99 or 100 numbers.
        </div><br>

        <div class="ques">How many numbers are there in a tambola ticket?</div>
        <div class="ans">There are 15 numbers in tambola ticket. 5 numbers in each row. There are 3 rows in a ticket, so
            5x3=15 numbers.</div><br>

        <div class="ques">What is grid size of Tambola Tickets?</div>
        <div class="ans">In classic tickets, grid size is 9x3. It means 9 columns and 3 rows.
            Each row has 5 filled and 4 blank cells.</div><br>

        <div class="ques">How are numbers filled in Tambola tickets. What is number pattern?</div>
        <div class="ans">Numbers are filled in tambola ticket by keeping in mind the concept of Kenu (sheet). There are
            6 tickets in Kenu. Each ticket has 9 columns and 3 rows. Total of 18 rows across 6 tickets.
            Number filling pattern is based on columns. Numbers are filled from 1 to 90 with each row having 5 numbers,
            so total of 5 x 18 = 90 numbers.

            The general concept is that a kenu has:
            <ul>

                <li>90 numbers across 6 tickets</li>
                <li>No number is repeated</li>
                <li>Each row has 5 numbers</li>
                <li>Column 1 has numbers from 1 to 9.</li>
                <li>Column 2 has numbers from 10 to 19.</li>
                <li>Column 3 has numbers from 20 to 29.</li>
                <li>Column 4 has numbers from 30 to 39.</li>
                <li>Column 5 has numbers from 40 to 49.</li>
                <li>Column 6 has numbers from 50 to 59.</li>
                <li>Column 7 has numbers from 60 to 69.</li>
                <li>Column 8 has numbers from 70 to 79.</li>
                <li>Column 9 has numbers from 80 to 90.</li>
                <li>Column 1 has 9 numbers, column 9 has 11 numbers and all other columns have 10 numbers each.</li>
                <li>There can be maximum 3 adjacent numbers in a row i.e. maximum of 3 adjacent columns can have numbers
                    in a row like column 3,4,5 in row 2 having numbers</li>
            </ul>
        </div><br>

        
        <div class="ques">What is difference between Tambola Ticket and Sheet?</div>
        <div class="ans">
            People often get confused between the tickets and sheet. Some people refer sheet as ticket i.e. set of 6.
            Ticket means 1 single ticket of 15 numbers. Sheet is a set of 6 tickets.
            <br>
            Sheet in general can be set of 2 or more tickets as per game rule. A more accurate name would be Kenu which means set of 6 tickets.
            </div><br>

        <div class="ques"></div>
        <div class="ans"></div><br>

        <div class="ques"></div>
        <div class="ans"></div><br>

        <div class="ques"></div>
        <div class="ans"></div><br>
    </div>

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
            <li><a href="./#download">HOW TO DOWNLOAD</a></li>
            <li><a href="./#play">HOW TO PLAY</a></li>
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