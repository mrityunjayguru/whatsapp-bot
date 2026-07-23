<%@ page language="java" contentType="text/html; charset=UTF-8"
    pageEncoding="UTF-8"%>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>WhatsApp API Test</title>

<script>
function sendMessage() {

    const to = document.getElementById("to").value;
    const message = document.getElementById("message").value;

    const url = "https://familiar-underwent-riddance.ngrok-free.dev/api/whatsapp/send1"
        + "?to=" + encodeURIComponent(to)
        + "&message=" + encodeURIComponent(message);

    fetch(url, {
        method: "POST",
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            to: to,
            message: message
        })
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("result").innerHTML = data;
    })
    .catch(error => {
        document.getElementById("result").innerHTML = "Error: " + error;
    });
}

/*

function loadContacts() {

    fetch("https://familiar-underwent-riddance.ngrok-free.dev/allcontactentity", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => response.json())
    .then(data => {

        let tbody = document.getElementById("contactBody");
        tbody.innerHTML = "";

        data.forEach(contact => {

            let row = "<tr>"
                    + "<td>" + contact.id + "</td>"
                  
                    + "<td>" + contact.tenantid + "</td>"
                    + "<td>" + contact.whatsappphonenumberid + "</td>"
                    + "<td>" + contact.phonenumber + "</td>"
                    + "<td>" + contact.whatsappprofilename + "</td>"
                    + "<td>" + contact.customname + "</td>"
                    + "<td>" + contact.email + "</td>"
                    + "<td>" + contact.tags + "</td>"
                    + "<td>" + contact.createdat + "</td>"
                    + "<td>" + contact.updatedat + "</td>"
                    + "<td>" + contact.payload + "</td>"                    + "</tr>";

            tbody.innerHTML += row;
        });

    })
    .catch(error => {
        console.error("Error:", error);
        alert("Unable to load contacts.");
    });
}

*/saveContactTagData


function loadAllConversationStatus()
{
    
    fetch("  https://familiar-underwent-riddance.ngrok-free.dev/api/allwebhookrequest", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => response.json())
    .then(data => {

        let tbody = document.getElementById("contactBodyConversationData");
        tbody.innerHTML = "";

        data.forEach(contact => {

            let row = "<tr>"
                    + "<td>" + contact + "</td>"
                  
                  
            tbody.innerHTML += row;
        });

    })
    .catch(error => {
        console.error("Error:", error);
        alert("Unable to load contacts.");
    });
    

}


function loadContacts() {


let allTags = [];
let tagOptions = "";

// First load all available tags
fetch("https://familiar-underwent-riddance.ngrok-free.dev/api/tags", {
    method: "GET",
    headers: {
        "Accept": "application/json",
        "ngrok-skip-browser-warning": "1"
    }
})
.then(response => {

    if (!response.ok) {
        throw new Error("Contact tags API failed: " + response.status);
    }

    return response.json();   // IMPORTANT: return the promise

})
.then(tags => {

    allTags = tags;
    
    allTags.forEach(function(tag) {

   
    tagOptions += 
        "<option value='" + tag.tagid +"'>" +
            tag.name +
        "</option>";

    });
    


})
.catch(error => {

    console.error("Error loading contact tags:", error);

});



    fetch("https://familiar-underwent-riddance.ngrok-free.dev/allcontactentity", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => response.json())
    .then(data => {

        let tbody = document.getElementById("contactBody");
        tbody.innerHTML = "";

       data.forEach(function (contact) {

    

    let row =
        "<tr id='id'>" +

            "<td>" + contact.id + "</td>" +

            "<td><input type='text' id='tenantid-" + contact.id +
            "' value='" + (contact.tenantid || "") + "'></td>" +

            "<td><input type='text' id='wpid-" + contact.id +
            "' value='" + (contact.whatsappphonenumberid || "") + "'></td>" +

            "<td><input type='text' id='phone-" + contact.id +
            "' value='" + (contact.phonenumber || "") + "'></td>" +

            "<td><input type='text' id='profile-" + contact.id +
            "' value='" + (contact.whatsappprofilename || "") + "'></td>" +

            "<td><input type='text' id='custom-" + contact.id +
            "' value='" + (contact.customname || "") + "'></td>" +

            "<td><input type='email' id='email-" + contact.id +
            "' value='" + (contact.email || "") + "'></td>" +


              "<td>" +
                    "<select multiple size='5' id='tags-" + contact.id + "'>" +
                        tagOptions +
                    "</select>" +
                "</td>" +


            "<td><input type='text' id='created-" + contact.id +
            "' value='" + (contact.createdat || "") + "'></td>" +

            "<td><input type='text' id='updated-" + contact.id +
            "' value='" + (contact.updatedat || "") + "'></td>" +

            "<td>" +
                "<textarea id='payload-" + contact.id +
                "' rows='3' cols='25'>" + (contact.payload || "") + "</textarea>" +
            "</td>" +

            "<td>" +
                "<button onclick='updateContact(" + contact.id + ")'>" +
                    "Update" +
                "</button>" +
            "</td>" +

            
            "<td>" +
                "<button onclick='viewConversation(" + contact.phonenumber + ")'>" +
                    "View Conversation" +
                "</button>" +
            "</td>" +

        "</tr>";

    

    tbody.insertAdjacentHTML("beforeend", row);
});

    })
    .catch(error => {
        console.error(error);
        alert("Unable to load contacts.");
    });
}

function updateContact(id) {


alert(Array.from(document.getElementById("tags-" + id).selectedOptions)
           .map(option => option.value));

    const contact = {
        id: id,
        tenantid: document.getElementById("tenantid-" + id).value,
        whatsappphonenumberid: document.getElementById("wpid-" + id).value,
        phonenumber: document.getElementById("phone-" + id).value,
        whatsappprofilename: document.getElementById("profile-" + id).value,
        customname: document.getElementById("custom-" + id).value,
        email: document.getElementById("email-" + id).value,
        tags: Array.from(document.getElementById("tags-" + id).selectedOptions)
           .map(option => option.value)
           .join(","),
        createdat: document.getElementById("created-" + id).value,
        updatedat: document.getElementById("updated-" + id).value,
        payload: document.getElementById("payload-" + id).value
    };


    
    fetch(`  https://familiar-underwent-riddance.ngrok-free.dev/webhook/update`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        },
        body: JSON.stringify(contact)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Update failed");
        }
        return response.json();
    })
    .then(data => {
        alert("Contact updated successfully.");
        loadContacts();
    })
    .catch(error => {
        console.error(error);
        alert("Unable to update contact.");
    });

}


function viewConversation(phonenumber) {

    let url = "  https://familiar-underwent-riddance.ngrok-free.dev/api/conversation/byphonenumber/"+phonenumber;
    
    fetch(url, {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => response.json())
    .then(data => {

        let tbody = document.getElementById("conversationBodyConversationData");
        tbody.innerHTML = "";

            data.forEach(contact => {
                let row = "<tr>"
                    + "<td>" + contact.messagebody + "</td>"
                    + "<td>" + contact.messagestatus + "</td>"
                    + "</tr>";

                tbody.innerHTML += row;
            });

    })
    .catch(error => {
        console.error("Error:", error);
        alert("Unable to load contacts.");
    });

}





function sendTagsMessage() {

    const tagName = document.getElementById("tagName").value;
    
    const url = "https://familiar-underwent-riddance.ngrok-free.dev/api/tags"
        + "?name=" + encodeURIComponent(tagName)
        

    fetch(url, {
        method: "POST",
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            name: tagName
        })
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("resulttags").innerHTML = data;
    })
    .catch(error => {
        document.getElementById("resulttags").innerHTML = "Error: " + error;
    });
}


function saveContactTagData() {

    const contactid = document.getElementById("contactid").value;
    const tagid = document.getElementById("tagid").value;

    const url = "https://familiar-underwent-riddance.ngrok-free.dev/api/contacttags"
        + "?contactid=" + encodeURIComponent(contactid)
        + "&tagid=" + encodeURIComponent(tagid);

    fetch(url, {
        method: "POST",
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            contactid: contactid,
            tagid: tagid
        })
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("resultcontacttags").innerHTML = data;
    })
    .catch(error => {
        alert("Error: " + error);
    });
}

  document.addEventListener("DOMContentLoaded", function () {

    fetch("https://familiar-underwent-riddance.ngrok-free.dev/api/tags", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Failed to fetch  tags");
        }
        return response.json();
    })
    .then(data => {

        let select = document.getElementById("tagid");
        select.innerHTML = "";

        // Default option
        select.innerHTML = '<option value="">--Select Tag--</option>';


        data.forEach(item => {
            let option = document.createElement("option");
            option.value = item.id;
            option.textContent = item.name;
            select.appendChild(option);
        });

    })
    .catch(error => {
        console.error("Error:", error);
        alert("Unable to load contact tags.");
    });



    // Contact  Data 

    
    fetch("https://familiar-underwent-riddance.ngrok-free.dev/allcontactentity", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Failed to fetch  tags");
        }
        return response.json();
    })
    .then(data => {

        let select = document.getElementById("contactid");
        select.innerHTML = "";

        // Default option
        select.innerHTML = '<option value="">--Select Contact--</option>';


        data.forEach(item => {
            let option = document.createElement("option");
            option.value = item.whatsappphonenumberid;
            option.textContent = item.whatsappprofilename;
            select.appendChild(option);
        });

    })
    .catch(error => {
        console.error("Error:", error);
        alert("Unable to load contact tags.");
    });


});


// Loading Tag Data 




function loadTags() {



    fetch("https://familiar-underwent-riddance.ngrok-free.dev/api/tags", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => response.json())
    .then(data => {

        let tbody = document.getElementById("tagBody");
        tbody.innerHTML = "";

       data.forEach(function (tag) {


    let row =
        "<tr id='id'>" +

             "<td>" + tag.id + "</td>" +
            "<td>" + tag.tagid + "</td>" +
            "<td>" + tag.name + "</td>" +            
           "<td>" + tag.createdat + "</td>" +
            "<td>" + tag.updatedat + "</td>" +


        "</tr>";

    

    tbody.insertAdjacentHTML("beforeend", row);
});

    })
    .catch(error => {
        console.error(error);
        alert("Unable to load tags.");
    });
}



async function contactSelected(whatsappphonenumberid) {
     
alert(whatsappphonenumberid);
let tagsdatalist=[];

           
    fetch("https://familiar-underwent-riddance.ngrok-free.dev/allcontactentity/by-whatsapp-phone/" + whatsappphonenumberid,
        {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    }
    )
        .then(response => {
            if (!response.ok) {
                throw new Error("Contact not found");
            }
            console.log(" Data ");
            console.log( response) ;
            console.log(" Data ");
            return response.json();
        })
        .then(async data => {



            //==========================tag data 


               
   await fetch("https://familiar-underwent-riddance.ngrok-free.dev/api/tags/bytagid/" + data.tags,
        {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    }
    )
        .then(response => {
            if (!response.ok) {
                throw new Error("Contact not found");
            }
            return response.json();
        })
        .then(datatag => {

            console.log("Tag Data Data ");
            console.log( datatag) ;
            datatag.forEach((list) => {
                tagsdatalist.push(list);
            });
            console.log("TagData Data ");
        })
        .catch(error => {
           console.log("Error  to feteching tags by tagids "+error);

        });
        

            //=====================================
            
            let tagHtml = tagsdatalist
                 .map(tag => `${tag.tagid} - ${tag.name}`)
             .join("<br>");

             console.log("tagsdatalist");
            console.log(tagsdatalist);
            console.log(tagHtml);
            console.log("tagsdatalist");

            document.getElementById("resultcontactentirytable").innerHTML =
                "<h3>Contact Details</h3>" +
                "<p>ID: " + data.id + "</p>" +
                "<p>Profile Name: " + data.whatsappprofilename + "</p>" +
            
                "<p>Tags: " +
                tagsdatalist.map(tag => tag.tagid + " - " + tag.name).join(", ") +
                "</p>"
            
                "<p>Created At: " + data.createdat + "</p>" +
                "<p>Updated At: " + data.updatedat + "</p>";

        })
        .catch(error => {
            document.getElementById("resultcontactentirytable").innerHTML =
                "<p style='color:red'>" + error.message + "</p>";
        });



}


// Saving Conversation Data 


function saveConversation() {

    let conversationData = {
        title: document.getElementById("title").value,
        assignedUserId: document.getElementById("assigned_user_id").value || null,
        contactId: document.getElementById("contact_id").value || null,
        status: document.getElementById("status").value,
        firstMessageAt: document.getElementById("first_message_at").value || null,
        lastMessageAt: document.getElementById("last_message_at").value || null,
        lastMessagePreview: document.getElementById("last_message_preview").value,
        resolvedAt: document.getElementById("resolved_at").value || null,
        tenantId: document.getElementById("tenant_id").value || null
    };

    console.log("=========conversationData===========");
    console.log(conversationData);    
    console.log("=========conversationData===========");

    fetch("https://familiar-underwent-riddance.ngrok-free.dev/api/conversation", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify(conversationData)   // <-- Don't wrap it
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("resultconversation").innerHTML = data;
    })
    .catch(error => {
        document.getElementById("resultconversation").innerHTML = "Error: " + error;
    });
}





function loadConversation() {

    fetch("https://familiar-underwent-riddance.ngrok-free.dev/api/conversation", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => response.json())
    .then(data => {

        let tbody = document.getElementById("conversationBody");
        tbody.innerHTML = "";

       data.forEach(function (tag) {

        console.log("Conversation Data");
        console.log(tag);
        console.log("Conversation Data");

    let row =
        "<tr id='id'>" +

             "<td>" + tag.id + "</td>" +
            "<td>" + tag.title + "</td>" +
            "<td>" + tag.assigned_user_id + "</td>" +
            "<td>" + tag.contact_id + "</td>" +
            "<td>" + tag.first_message_at + "</td>" +
            "<td>" + tag.last_message_at + "</td>" +
            "<td>" + tag.last_message_preview + "</td>" +
            "<td>" + tag.resolved_at + "</td>" +
              "<td>" + tag.status + "</td>" +
              "<td>" + tag.tenant_id + "</td>" +
              "<td>" + tag.unread_count + "</td>" +
            "<td>" + tag.whatsapp_phone_number_id + "</td>" +
            "<td>" + tag.messagebody + "</td>" +
            "<td>" + tag.messagestatus + "</td>" +
            "<td>" + tag.profilename + "</td>" +

            "<td>" + tag.created_at + "</td>" +
            "<td>" + tag.updated_at + "</td>" +


        "</tr>";

    

    tbody.insertAdjacentHTML("beforeend", row);
});

    })
    .catch(error => {
        console.error(error);
        alert("Unable to load tags.");
    });
}



function loadUniqueConversation() {

    fetch("https://familiar-underwent-riddance.ngrok-free.dev/api/conversation/byuniquephonenumber", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => response.json())
    .then(data => {

        let tbody = document.getElementById("uniqueconversationBody");
        tbody.innerHTML = "";

       data.forEach(function (tag) {

        console.log("Conversation Data");
        console.log(tag);
        console.log("Conversation Data");

    let row =
        "<tr id='id'>" +

             "<td>" + tag.id + "</td>" +
            "<td>" + tag.title + "</td>" +
            "<td>" + tag.assigned_user_id + "</td>" +
            "<td>" + tag.contact_id + "</td>" +
            "<td>" + tag.first_message_at + "</td>" +
            "<td>" + tag.last_message_at + "</td>" +
            "<td>" + tag.last_message_preview + "</td>" +
            "<td>" + tag.resolved_at + "</td>" +
              "<td>" + tag.status + "</td>" +
              "<td>" + tag.tenant_id + "</td>" +
              "<td>" + tag.unread_count + "</td>" +
            "<td>" + tag.whatsapp_phone_number_id + "</td>" +
            "<td>" + tag.messagebody + "</td>" +
            "<td>" + tag.messagestatus + "</td>" +
            "<td>" + tag.profilename + "</td>" +
              "<td>" + tag.phonenumber + "</td>" +

            "<td>" + tag.created_at + "</td>" +
            "<td>" + tag.updated_at + "</td>" +
                "<td>"
                + "<button onclick=\"viewMessageByPhonenumber('" + tag.phonenumber + "')\">View Message</button>"
                + "</td>"

        "</tr>";

    

    tbody.insertAdjacentHTML("beforeend", row);
});

    })
    .catch(error => {
        console.error(error);
        alert("Unable to load tags.");
    });
}



function viewMessageByPhonenumber(phonenumber)
{
    alert(phonenumber);

      fetch("https://familiar-underwent-riddance.ngrok-free.dev/api/messages/byphonenumber/"+phonenumber, {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "ngrok-skip-browser-warning": "1"
        }
    })
    .then(response => response.json())
    .then(data => {


        console.log(" Message Data ");
        console.log(data);
        console.log(" Message Data ");

document.getElementById("messageviewdata").innerHTML =
    "<pre>" + JSON.stringify(data, null, 2) + "</pre>";
    

    })
    .catch(error => {
        console.error(error);
        alert("Unable to load tags.");
    });



}

</script>

</head>
<body>

<h2>Send WhatsApp Message</h2>

<table>
    <tr>
        <td>Mobile Number</td>
        <td>
            <input type="text" id="to" value="919650403954">
        </td>
    </tr>

    <tr>
        <td>Message</td>
        <td>
            <textarea id="message" rows="4" cols="40">HELLO TEST</textarea>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <button onclick="sendMessage()">Send</button>
        </td>
    </tr>
</table>

<hr>

<div id="result"></div>




<a href="#" onclick="loadContacts(); return false;">All Contact</a>

<br><br>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tenant ID</th>
            <th>WhatsApp Phone Number ID</th>
            <th>Phone</th>
            <th>WhatsApp Profile Name</th>
            <th>Custom Name</th>
            <th>Email</th>
            <th>Tags</th>
            <th>Created At</th>
            <th>Updated At</th>
            <th>Payload</th>
        </tr>
    </thead>

    <tbody id="contactBody">

    </tbody>

</table>


<hr>
<div>

<div style="background-color: burlywood;">

    
<table border="1">
    <thead>
        <tr>
            <th>Data</th>
            
        </tr>
    </thead>

    <tbody id="conversationBodyConversationData">

    </tbody>

</table>
</div>


<h2>Add Tag</h2>

<table>
    <tr>
        <td>Tage Name</td>
        <td>
            <input type="text" id="tagName">
        </td>
    </tr>

    

    <tr>
        <td colspan="2">
            <button onclick="sendTagsMessage()">Save</button>
        </td>
    </tr>
</table>
<hr>



<a href="#" onclick="loadTags(); return false;">All Tags</a>

<br><br>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tag ID</th>
            <th>Tag Name</th>
            <th>Created At</th>
            <th>Updated At</th>
            
        </tr>
    </thead>

    <tbody id="tagBody">

    </tbody>

</table>

<hr>

<div id="resulttags"></div>


</div>


<hr>
<hr>


<h2>Adding Data in Contact Tag Table </h2>

<table>
    <tr>
        <td>Contact ID</td>
        <td>
            <select id="contactid" name="contactid"  onchange="contactSelected(this.value)">
                 <option>Loading...</option>
            </select>
        </td>
    </tr>

    <tr>
        <td>Tag ID</td>
        <td>
            <select id="tagid" name="tagid">
                 <option>Loading...</option>
            </select>
        </td>
    </tr>

    

    <tr>
        <td colspan="2" style="display: block;">
            <button onclick="saveContactTagData()">Save</button>
        </td>
    </tr>
</table>



<div id="resultcontacttags" style="display: block;"></div>


<div id="resultcontactentirytable" style="display: block;"></div>



<hr>
<hr>


<hr>



// Saving Conversation Data
<div>


<h2>Save Conversation</h2>

<table>
    

    <tr>
        <td>Conversation Title</td>
        <td>
            <input type="text" id="title" name="title" placeholder="Enter conversation title">
        </td>
    </tr>

    <tr>
        <td>Assigned User ID</td>
        <td>
            <input type="number" id="assigned_user_id" name="assigned_user_id">
        </td>
    </tr>

    <tr>
        <td>Contact ID</td>
        <td>
            <input type="number" id="contact_id" name="contact_id">
        </td>
    </tr>

    <tr>
        <td>Status</td>
        <td>
            <input type="text" id="status" name="status">
        </td>
    </tr>

    <tr>
        <td>Tenant ID</td>
        <td>
            <input type="number" id="tenant_id" name="tenant_id">
        </td>
    </tr>

    <tr>
        <td>WhatsApp Phone Number ID</td>
        <td>
            <input type="text" id="whatsapp_phone_number_id" name="whatsapp_phone_number_id">
        </td>
    </tr>

    <tr>
        <td>Payload</td>
        <td>
            <textarea id="payload" name="payload" rows="4" cols="40"></textarea>
        </td>
    </tr>

    <!-- Read-only fields -->

    <tr>
        <td>ID</td>
        <td>
            <input type="number" id="id" value="2" readonly>
        </td>
    </tr>

    <tr>
        <td>Created At</td>
        <td>
            <input type="text" id="created_at" value="2026-07-16T16:25:15.372521" readonly>
        </td>
    </tr>

    <tr>
        <td>Updated At</td>
        <td>
            <input type="text" id="updated_at" value="2026-07-16T16:25:15.372521" readonly>
        </td>
    </tr>

    <tr>
        <td>First Message At</td>
        <td>
            <input type="text" id="first_message_at" readonly>
        </td>
    </tr>

    <tr>
        <td>Last Message At</td>
        <td>
            <input type="text" id="last_message_at" readonly>
        </td>
    </tr>

    <tr>
        <td>Last Message ID</td>
        <td>
            <input type="text" id="last_message_id" readonly>
        </td>
    </tr>

    <tr>
        <td>Last Message Preview</td>
        <td>
            <textarea id="last_message_preview" rows="2" readonly></textarea>
        </td>
    </tr>

    <tr>
        <td>Resolved At</td>
        <td>
            <input type="text" id="resolved_at" readonly>
        </td>
    </tr>

    <tr>
        <td>Unread Count</td>
        <td>
            <input type="number" id="unread_count" readonly>
        </td>
    </tr>


    

    <tr>
        <td colspan="2">
            <button onclick="saveConversation()">Save Conversation</button>
        </td>
    </tr>
</table>
<hr>



<a href="#" onclick="loadConversation(); return false;">All Conversation</a>

<br><br>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Conversation Title</th>
            <th> Assigned User Id </th>
            <th>Contact Id</th>
            <th>First Message At</th>
            <th>Last Message At</th>
            <th>Last Message Preview</th>
            <th>Resolved At</th>
            <th>Status</th>
            <th>Tenant Id</th>
            <th>Unread Count</th>
            <th>WhatsApp Phone Number Id</th>
             <th> Message Body </th>
            <th> Message Status </th>
            <th> Profile Name </th>

            <th>Created At</th>
            <th>Updated At</th>
            
        </tr>
    </thead>

    <tbody id="conversationBody">

    </tbody>

</table>

<hr>

<div id="resultconversation"></div>




<a href="#" onclick="loadUniqueConversation(); return false;">Unique Conversation</a>

<br><br>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Conversation Title</th>
            <th> Assigned User Id </th>
            <th>Contact Id</th>
            <th>First Message At</th>
            <th>Last Message At</th>
            <th>Last Message Preview</th>
            <th>Resolved At</th>
            <th>Status</th>
            <th>Tenant Id</th>
            <th>Unread Count</th>
            <th>WhatsApp Phone Number Id</th>
             <th> Message Body </th>
            <th> Message Status </th>
            <th> Profile Name </th>
            <th> phonenumber </th>
            <th>Created At</th>
            <th>Updated At</th>
            <th>  Action  </th>
            
        </tr>
    </thead>

    <tbody id="uniqueconversationBody">

    </tbody>

</table>

Message Data
<div id="messageviewdata">


</div>

</div>


    <div>
     <hr style="height: 20px;color: red;">

        <a href="#" onclick="loadAllConversationStatus(); return false;"> All Conversation Status </a>
    
<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tenant ID</th>
            <th>WhatsApp Phone Number ID</th>
            <th>Phone</th>
            <th>WhatsApp Profile Name</th>
            <th>Custom Name</th>
            <th>Email</th>
            <th>Tags</th>
            <th>Created At</th>
            <th>Updated At</th>
            <th>Payload</th>
        </tr>
    </thead>

    <tbody id="contactBodyConversationData">

    </tbody>

</table>


    
    <hr style="height: 20px; color:red">                

    </div>


</body>
</html>