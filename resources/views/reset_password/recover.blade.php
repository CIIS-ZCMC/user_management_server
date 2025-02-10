<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New User Registration Notification</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333;">
    <div style="display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f9f9f9;">
        <div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
            <!-- Header Section -->
            <div style="text-align: left; padding: 20px 0;">
                <table style="width: 100%; text-align: left;">
                    <tr>
                        <td style="width: 60px; vertical-align: top;">
                            <img src="https://zcmc.online/zcmc.png" 
                                alt="Telemedicine Logo" style="width: 60px;">
                        </td>
                        <td style="vertical-align: center;">
                            <table style="text-align: left;">
                                <tr>
                                    <td style="font-size: 10px; font-weight: bold; color: rgba(0,0,0,0.7);">
                                        Republic of the Philippines
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="width: 100%; height: 1px; background-color: rgba(0,0,0,0.7);"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: rgba(0,0,0,0.7); margin: 0; font-weight: bold;">
                                        Zamboanga City Medical Center Portal
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 9px; font-weight: bold; color: rgba(0,0,0,0.7);">
                                        Dr. Evangelista St., Sta. Catalina, Zamboanga City, Philippines
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <!-- <div style="background-color: #008088; color: #fff; padding: 10px; text-align: center;">
                <h2 style="font-size: 22px; margin: 0;">New User Registered</h2>
            </div> -->

            <!-- Content Section -->
            <div style="padding: 20px; background-color: #f9f9f9;">
                <p style="margin: 0 0 10px; font-size: 14px; color: #333;">
                Thank you for reporting the issue you encountered in the system.<br/><br/>
                As part of resolving the issue you encountered. The problem has now been resolved, and you can sign in to your account using the credentials provided.<br/><br/>
                For your security, this temporary password is accessible only to you. Upon logging in, you will be prompted to create a new password to ensure your account remains secure.
                </p><br/>
                <p style="margin: 10px 0; font-size: 14px; font-weight: bold; color: #333;">Account Details:</p>
                <table style="width: 100%; border-spacing: 0; border-collapse: collapse; margin: 10px 0;">
                    <tr style="background-color: #f9f9f9;">
                        <td style="padding: 10px; font-size: 13px; color: #333; font-weight: bold; border-bottom: 1px solid #ddd; vertical-align: top;">Employee ID:</td>
                        <td style="padding: 10px; font-size: 13px; color: #555; border-bottom: 1px solid #ddd;">{{$EmployeeID}}</td>
                        <td style="width:12rem; color: #555; border-bottom: 1px solid #ddd;"></td>
                    </tr>
                    <tr style="background-color: #fff;">
                        <td style="padding: 10px; font-size: 13px; color: #333; font-weight: bold; border-bottom: 1px solid #ddd; vertical-align: top;">Password:</td>
                        <td style="padding: 10px; font-size: 13px; color: #555; border-bottom: 1px solid #ddd;">{{$Password}}</td>
                        <td style="width:12rem; color: #555; border-bottom: 1px solid #ddd;"></td>
                    </tr>
                </table>
                <p style="margin: 10px 0; font-size: 13px; color: #333;">Please log in to your account using the credentials provided. You can update your password after logging in for added security.</p>
                <div style="text-align: center; margin-top: 25px;">
                    <a href="https://zcmc.online/login" style="display: inline-block; background-color: #0f5721; color: #fff; padding: 8px 16px; text-decoration: none; font-size: 12px; font-weight: bold; border-radius: 5px; text-align: center; transition: background-color 0.3s;">
                        Log In to Your Account
                    </a>
                </div>
            </div>
            
            <!-- Footer Section -->
            <div style="text-align: center; padding: 20px 0; font-size: 12px; color: #666; border-top: 1px solid #eaeaea;">
                <p style="margin: 0;">This email was sent from the <strong>ZCMC Portal Platform</strong>.</p>
                <p style="margin: 5px 0;">For support, contact us at <a href="mailto:ciis.zcmc@gmail.com" style="color: #0f5721; text-decoration: none;">ciis.zcmc@gmail.com</a></p>
                <p style="margin: 5px 0;">&copy; 2023 ZCMC-Portal. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
