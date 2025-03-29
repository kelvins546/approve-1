<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="guideStyle.css">
    <title>Guidlines</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;700&display=swap');
        @import url("https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400;1,700&display=swap");
        @import url("https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;600&display=swap"
    );

        /* Reset some default styles */
        *{
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Hanken Grotesk', Arial, sans-serif;
        }

        body {
        background-color: #fff;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        display: flex;
        color: #545454;
        flex-direction: column;
        min-height: 100vh;
        background-color: #FAF9F6;
    }

        .navbar {
        background-color: #fff;
        padding: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        color: #545454;
        position: sticky;
        top: 0;
        z-index: 10;
        width: 100%;
        display: flex;
        align-items: center;
        /* Center items vertically */
        justify-content: space-between;
        /* Distribute space between items */
    }

    /* Navigation main container */
    .nav-main {
        display: flex;
        align-items: center;
        /* Center items vertically */
        gap: 20px;
    }

    .nav-content {
        background-image: url('images/f1.png');
        background-size: cover;
        background-position: center center;
        background-attachment: fixed;
        background-repeat: no-repeat;
        padding: 97px 0;


    }

    .nav-content-cont {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        margin-left: 70px;

    }

    

    .nav-main {
        display: flex;
        align-items: center;
        gap: 20px;

        /* Add some spacing between nav-main and nav-content */
    }

    .nav-btn {
        background-color: transparent;
        color: #545454;
        border: none;
        font-size: 16px;
        margin-top: 12px;
        margin-left: 30px;
        cursor: pointer;
        text-align: center;
        display: inline-block;
        transition: color 0.3s ease, text-decoration 0.3s ease;
    }

    /* Hover effect on button */
    .nav-btn:hover {

        text-decoration: underline;
    }

    .icon-btn {
        background-color: #f4f5f6;
        border: 2px solid #000;
        border-radius: 50%;
        cursor: pointer;
        padding: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 20px;
        /* Adjust this value as needed */
        transition: background-color 0.3s ease, border-color 0.3s ease;
        z-index: 99999;
        position: relative;
        /* Enable relative positioning */
        right: -100px;
    }


    .icon-btn {
        z-index: 99999;
        margin-left: auto;
        width: 40px;
        /* Set to desired size */
        height: 40px;
    }




    .nav-main>.icon-btn:hover {
        background-color: #f4f4f9;
        /* Light background on hover */
        border-color: #000;
        /* Darker border on hover */
    }

    .nav-main>.icon-btn:hover .user-icon {
        color: #000;
        /* Darker icon color on hover */
    }

    .user-icon {
        font-size: 24px;
        /* Icon size */
        color: #545454;
        transition: color 0.3s ease;
        /* Smooth color change on hover */
    }

    .user-icon:hover {
        color: #545454;
        /* Darken color on hover */
    }

    .navbar-links {
        margin-left: 100px;
        margin-right: 90px;
    }

    .navbar-links a {
        color: #545454;
        padding: 3px;
        text-decoration: none;
        margin: 20px;
        display: inline-block;

    }

    .navbar-links a:hover {
        text-decoration: underline;
    }

    .navbar-logo {
        height: 90px;
        width: auto;
        margin-right: 0px;
        margin-left: 30px;
        margin-top: 0;
    }

    .navbar-text {
        font-family: "Times New Roman", Times, serif;
        font-size: 36px;
        font-weight: bold;
        white-space: nowrap;
        color: #000 !important;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);

    }

    /* Dropdown Container */
    .dropdown {
        position: relative;
        display: inline-block;
        margin-bottom: 10px;
        z-index: 1;
    }

    /* Dropdown Button */
    .dropdown-btn {
        padding: 5px 20px;
        background-color: #ff7701;
        color: #fff;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.3s ease;
    }

    /* Dropdown Arrow */
    .dropdown-btn::after {
        content: '';
        width: 0;
        height: 0;
        border-top: 5px solid transparent;
        border-bottom: 5px solid transparent;
        border-left: 5px solid #fff;

        margin-left: 10px;
        transition: transform 0.3s ease;
        transform: rotate(270deg);
    }

    /* Dropdown Content */
    .dropdown-content1 {
        display: none;
        position: absolute;
        background-color: #fff;
        min-width: 180px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
        margin-top: 0;
        border-radius: 4px;
        left: 0 !important;
        right: auto;

    }


    /* Show Dropdown Content on Hover */
    .dropdown:hover .dropdown-content1 {
        display: block;
    }

    /* Dropdown Links */
    .dropdown-content1 a {
        padding: 5px 5px;
        text-decoration: none;
        display: block;
        color: #333;
        /* Link text color */
        transition: background-color 0.3s ease;
    }

    /* Dropdown Links Hover Effect */
    .dropdown-content1 a:hover {
        background-color: #ccc;
        /* Darker hover background color */
    }


    .nav-title h1 {
        font-size: 78px;
        color: #f6efe0;
        text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        font-style: italic;
        font-weight: bold;
        width: 700px;
        font-family: 'Hanken Grotesk', Arial, sans-serif;
    }




        .boxFP {
            height: 240px;
            width: 1278px;
            border: 1px solid #fff;
            background-image: url(Copy\ of\ Lost\ and\ Found.png);
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            font-family: 'Canva Sans', sans-serif;
            font-weight: bold;
            font-size: 50px;
            color: #fff;
            text-align: center;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);

            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .orangeBox {
            margin-top: 30px;
            margin-bottom: 30px;
            height: 110px;
            width: 1580px;
            background-color: #f8d0b4;
        }

        .orangeBtxt {
            font-family: 'Work Sans', sans-serif;
            font-size: 18px;
            text-align: justify;
            font-weight: normal;
            color: #726e6e;
            padding-top: 35px;
            margin-left: 60px;
            margin-right: 60px;
        }

        /*PARENT1*/
        .container2 {
            display: flex;
            align-items: center; /* Align text and link vertically */
            gap: 10px; /* Space between heading and link */
            margin-top: 30px;
            margin-left: 80px;
            height: 60px;
            width: 1000px;
            background-color: #fff;
            box-shadow: 5px 5px 15px rgba(0.1, 0.1, 0.1, 0.1);
        }   

        .claimh3 {
            padding-top: 5px;
            font-family: 'Canva Sans', sans-serif;
            font-weight: normal;
            font-size: 25px;
            color: #726e6e;
            padding-left: 20px;
        }

        .claimDownloadLink {
            font-family: 'Canva Sans', sans-serif;
            font-style: italic;
            font-size: 16px;
            color: #007bff;
            text-decoration: none;
            padding-top: 7px;
            transition: background 0.3s ease;
        }

        .claimDownloadLink:hover {
            color: orange;
        }


        .container3 {
            display: flex;
            align-items: center; /* Align text and link vertically */
            gap: 10px; /* Space between heading and link */
            margin-top: 30px;
            margin-left: 80px;
            height: 60px;
            width: 1000px;
            background-color: #fff;
            box-shadow: 5px 5px 15px rgba(0.1, 0.1, 0.1, 0.1);
        }   

        .losth3 {
            padding-top: 5px;
            font-family: 'Canva Sans', sans-serif;
            font-weight: normal;
            font-size: 25px;
            color: #726e6e;
            padding-left: 20px;
        }

        .reportedLostItemDownloadLink {
            font-family: 'Canva Sans', sans-serif;
            font-style: italic;
            font-size: 16px;
            color: #007bff;
            text-decoration: none;
            padding-top: 7px;
            transition: background 0.3s ease;
        }

        .reportedLostItemDownloadLink:hover {
            color: orange;
        }

        .container4{
            display: flex;
            align-items: center; /* Align text and link vertically */
            gap: 10px; /* Space between heading and link */
            margin-top: 30px;
            margin-left: 80px;
            height: 60px;
            width: 1000px;
            background-color: #fff;
            box-shadow: 5px 5px 15px rgba(0.1, 0.1, 0.1, 0.1);
        }   

        .foundh3 {
            padding-top: 5px;
            font-family: 'Canva Sans', sans-serif;
            font-weight: normal;
            font-size: 25px;
            color: #726e6e;
            padding-left: 20px;
        }

        .reportedFoundItemDownloadLink {
            font-family: 'Canva Sans', sans-serif;
            font-style: italic;
            font-size: 16px;
            color: #007bff;
            text-decoration: none;
            padding-top: 7px;
            transition: background 0.3s ease;
        }

        .reportedFoundItemDownloadLink:hover {
            color: orange;
        }

        
        /* Footer */
    .footer {
        background-color: #fff;
        padding: 20px 0;
        margin-top: 60px;
        color: #545454;
        font-family: 'Hanken Grotesk', sans-serif;
        width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        position: relative;
        text-align: center;
    }

    .footer-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        /* Space out logo and contact text */
        width: 90%;
        margin: 0 auto;
        padding-bottom: 20px;
    }

    .footer-logo {
        align-self: flex-start;
        margin-top: 15px;
    }

    .footer-logo img {
        max-width: 70px;
    }


    .footer-contact {
        text-align: right;
        /* Align text to the right */
        font-size: 14px;
        margin-left: auto;
        width: 20%;
        margin-bottom: 25px;
    }

    .footer-contact h4 {
        font-size: 18px;
        margin-bottom: 10px;
    }

    .footer-contact p {
        font-size: 14px;
        margin-top: 0;

    }

    .all-links {
        display: flex;

        width: 100%;
        margin-top: 20px;
        position: absolute;

        justify-content: center;
    }

    .footer-others {
        display: flex;
        justify-content: center;
        /* Align links in the center */
        gap: 30px;
        top: 190px;
        left: 30%;
        margin-left: 140px;
        margin-top: 20px;
        transform: translateX(-50%);
    }


    .footer-others a {
        color: #545454;
        text-decoration: none;
        font-size: 14px;
    }

    .footer-separator {
        width: 90%;
        height: 1px;
        background-color: #545454;
        margin: 10px auto;
        border: none;
        position: absolute;
        bottom: 40px;
        left: 50%;
        margin-top: 20px;
        transform: translateX(-50%);
    }

    .footer-text {
        font-size: 14px;
        margin-top: 20px;
        color: #545454;
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);

    }   

    .LAFh1 {
        font-family: "League Spartan", sans-serif;
        font-optical-sizing: auto;
        font-weight: bold;
    }
        
    </style>

<body>
<div class="navbar">
        <div class="nav-main">
            <img src="images/logo.png" alt="Logo" class="navbar-logo">
            <span class="navbar-text">UNIVERSITY OF CALOOCAN CITY</span>
            <div class="navbar-links">
                <a href="found_report.php">Home</a>
                <a href="guidelines.php">Guidelines</a>
                <div class="dropdown">
                    <button class="nav-btn">Browse Reports</button>
                    <div class="dropdown-content1">
                        <a href="userview.php">Found Reports</a>
                        <a href="lost_reports.php">Lost Reports</a>
                    </div>
                </div>
            </div>
            <!-- Move the icon button inside nav-main -->
            <button class="icon-btn" onclick="openModal('loginclickmodal')">
                <ion-icon name="person" class="user-icon"></ion-icon>
            </button>
        </div>
    </div>
    <div class="nav-content">
        <div class="nav-content-cont">
            <div class="nav-title">
                <h1 class="LAFh1">GUIDELINES AND PROCEDURES</h1>
            </div>
        </div>
    </div>    

    <div class="orangeBox">
    <p class="orangeBtxt">
        <b>IMPORTANT NOTE:</b> The school provides a Lost and Found service to assist in the recovery of lost items. 
        However, the school does not assume any responsibility for lost, stolen, or unclaimed items. 
        The use of this service is voluntary, and all individuals acknowledge the following terms upon reporting or claiming an item.
    </p>
    </div>

    <div class="container2">
        <h3 class="claimh3">
            <span style="color: #726e6e; font-weight: bold;">CLAIMING AN ITEM</span> Guidelines, Policy, and Procedures.
        </h3>
        <a href="uploads/GUIDE IN CLAIMING.pdf" download class="claimDownloadLink">View Here</a>
    </div>

    <div class="container3">
        <h3 class="losth3">
            <span style="color: #726e6e;font-weight: bold">REPORTED LOST ITEMS</span> Guidelines and Policy
        </h3>
        <a href="uploads/GUIDE IN CLAIMING.pdf" download class="reportedLostItemDownloadLink">View Here</a>
    </div>

    <div class="container4">
        <h3 class="foundh3">
            <span style="color: #726e6e; font-weight: bold">REPORTED FOUND ITEMS</span> Guidelines and Policy
        </h3>
        <a href="uploads/GUIDE IN CLAIMING.pdf" download class="reportedFoundItemDownloadLink">View Here</a>
    </div>


    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="images/logo.png" alt="Logo" />
                <img src="images/caloocan.png" alt="Logo" />

            </div>
            <div class="all-links">
                <nav class="footer-others">
                    <a href="">ABOUT US</a>
                    <a href="">TERMS</a>
                    <a href="">FAQ</a>
                    <a href="">PRIVACY</a>
                </nav>
            </div>


            <div class="footer-contact">
                <h4>Contact us</h4>
                <p>This website is currently under construction. For futher inquires, please contact us at
                    universityofcaloocan@gmailcom</p>
            </div>
            <hr class="footer-separator">
            <p class="footer-text">&copy; University of Caloocan City, All rights reserved.</p>
        </div>
    </footer>
</body>
</head>

</html>