<!DOCTYPE html>
<!-- <html lang="en"  data-theme="light"> -->
<html lang="en">
  <head>
  
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">

    <meta http-equiv="Content-Type" content="text/html charset=UTF-8" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>VelRiders Email</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap"
      rel="stylesheet"
    />

    <style>
      * {
        font-family: "Poppins", sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      body {
        background-color: #ffffff !important;
        color: #000000;
      }

      /* Dark mode styles (for clients that support it) */
      @media (prefers-color-scheme: dark) {
  /* Dark mode styles */
}
    </style>
  </head>

  <body style="margin: 0; padding: 0; background-color: #ffffff; font-family: 'Poppins', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff">
      <tr>
        <td align="center">
          <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff;box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
            <tr>
              <td style="background-color: #38a4a6;text-align: center;color: white;max-height: 70px;height: 70px;background-image: url({{asset('images/email_images/Header.png')}}); background-repeat: no-repeat;background-size: cover;background-position: center;">
                <img src="{{asset('images/email_images/VelRidersLogo.png')}}" alt="VelRiders Logo" width="100" height="70" style="object-fit: contain" />
              </td>
            </tr>
            <tr>
                <td style="padding: 29px 30px;background-image: url({{asset('images/email_images/BodyBackGround.png')}}); background-size: cover;background-repeat: no-repeat; background-position: center;background-color: #f4f4f4;">
            
                    @yield('content')
                    
                </td>
            </tr>
            <tr>
              <td
                style="
                  background-color: #38a4a6;
                  text-align: center;
                  color: white;
                  background-image: url({{asset('images/email_images/Footer.png')}});
                  background-repeat: no-repeat;
                  background-size: cover;
                  background-position: center;
                  padding: 10px 0;
                "
              >
                <!-- Social Section -->
                <table
                cellpadding="0"
                cellspacing="0"
                border="0"
                align="center"
                style="
                  margin: 0 auto;
                 background-image: url({{asset('images/email_images/WhiteBackground.png')}});
                  background-repeat: no-repeat;
                  background-position: center;
                  background-size: cover;
                  border-radius: 12px;
                  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                  width: 250px;
                  padding: 0 5px;
                "
              >
                  <tr>
                    <td align="center" style="padding: 10px">
                      <img
                        src="{{asset('images/email_images/icon/Facebook.png')}}"
                        alt="Facebook"
                        width="28"
                        height="28"
                        style="display: block; filter: invert(0) !important"
                      />
                    </td>
                    <td align="center" style="padding: 10px">
                      <img
                        src="{{asset('images/email_images/icon/Twitter.png')}}"
                        alt="Twitter"
                        width="28"
                        height="28"
                        style="display: block; filter: invert(0) !important"
                      />
                    </td>
                    <td align="center" style="padding: 10px">
                      <img
                        src="{{asset('images/email_images/icon/Linkedin.png')}}"
                        alt="LinkedIn"
                        width="28"
                        height="28"
                        style="display: block; filter: invert(0) !important"
                      />
                    </td>
                    <td align="center" style="padding: 10px">
                      <img
                        src="{{asset('images/email_images/icon/Instagram.png')}}"
                        alt="Instagram"
                        width="28"
                        height="28"
                        style="display: block; filter: invert(0) !important"
                      />
                    </td>
                  </tr>
                </table>
                <!-- Footer Text -->
                <table
                  cellpadding="0"
                  cellspacing="0"
                  border="0"
                  align="center"
                  style="
                    margin: 0 auto;
                    background-color: #38a4a6;
                    border-radius: 12px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                    width: 250px;
                  ">
                  <tr>
                    <span style="font-size: 8px">
                      © 2025. All Rights Reserved By VelRiders Private Limited
                    </span>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
