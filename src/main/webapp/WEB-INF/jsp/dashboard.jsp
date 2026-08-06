<%@ page language="java" contentType="text/html; charset=UTF-8"
    pageEncoding="UTF-8"%>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>WhatsApp API Test</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f5f5f5;
    }

    .container {
        width: 100%;
        margin: 40px auto;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px #ccc;
    }

    h2 {
        text-align: center;
        color: #333;
    }

    label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }

    input {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    button {
        width: 100%;
        margin-top: 20px;
        padding: 10px;
        background: #007bff;
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 4px;
        font-size: 16px;
    }

    button:hover {
        background: #0056b3;
    }

    #message {
        margin-top: 15px;
        text-align: center;
        font-weight: bold;
    }
</style>



<script>
document.addEventListener("DOMContentLoaded", function () {

    // Fetch total contacts
    fetch("https://familiar-underwent-riddance.ngrok-free.dev/allcontactentity", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Failed to fetch contacts");
        }
        return response.json();
    })
    .then(data => {
        document.getElementById("totalContacts").textContent = data.length;
    })
    .catch(error => {
        console.error("Error:", error);
        document.getElementById("totalContacts").textContent = "0";
    });

    // Fetch total conversations
    fetch("https://familiar-underwent-riddance.ngrok-free.dev/api/conversation", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Failed to fetch conversations");
        }
        return response.json();
    })
    .then(data => {
        document.getElementById("totalConversations").textContent = data.length;
    })
    .catch(error => {
        console.error("Error:", error);
        document.getElementById("totalConversations").textContent = "0";
    });




    // Fetch total Messages
    fetch("https://familiar-underwent-riddance.ngrok-free.dev/chatboatentity", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Failed to fetch messages");
        }
        return response.json();
    })
    .then(data => {
        
        document.getElementById("totalMessages").textContent = data.length;
    })
    .catch(error => {
        console.error("Error:", error);
        document.getElementById("totalMessages").textContent = "0";
    });

});


</script>

</head>
<body>

<div class="container">
    <h2>Dashboard</h2>

        <div style="font-size:24px; font-weight:bold; color:#007bff; text-align:center;">
            Total Contacts: <span id="totalContacts">0</span>
        </div>

        <div style="font-size:24px; font-weight:bold; color:#007bff; text-align:center;">
           Total Conversation : <span id="totalConversations">0</span>
        </div>

        <div style="font-size:24px; font-weight:bold; color:#007bff; text-align:center;">
           Total Message : <span id="totalMessages">0</span>
        </div>

</div>




    




</body>
</html>Dash Board