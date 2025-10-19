<?php
if (isset($_GET['backend'])) {
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Headers: Content-Type");
  header("Content-Type: application/json");

  // ✅ Database connection (FeedForward)
  $pdo = new PDO("mysql:host=127.0.0.1;dbname=FeedForward;charset=utf8mb4", "root", "", [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  // ✅ Handle GET request: Fetch pending donations
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $stmt = $pdo->query("SELECT * FROM pickup WHERE status = 'Pending'");
      echo json_encode($stmt->fetchAll());
      exit;
  }

  // ✅ Handle POST request: Update donation status
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $data = json_decode(file_get_contents("php://input"), true);
      $pickup_id = $data['pickup_id'];
      $action = strtolower($data['action']);

      if (!in_array($action, ['accept', 'reject'])) {
          http_response_code(400);
          echo json_encode(['error' => 'Invalid action']);
          exit;
      }

      $newStatus = $action === 'accept' ? 'Accepted' : 'Full';
      $pdo->prepare("UPDATE pickup SET status=? WHERE pickup_id=?")->execute([$newStatus, $pickup_id]);
      echo json_encode(['message' => "Donation $newStatus successfully."]);
      exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NGO Portal</title>
<style>
body{font-family:"Segoe UI",sans-serif;margin:0;padding:0;background:#e6f7f2;color:#333;overflow-x:hidden;animation:fadeInBody 1s ease-in;}
@keyframes fadeInBody{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.index-page,.dashboard-page{height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;}
.index-page{background:linear-gradient(135deg,#d0f0eb,#fff);animation:slideIn 1s ease-in-out;}
@keyframes slideIn{from{transform:translateY(-50px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.index-page h1{font-size:32px;color:#047857;margin-bottom:10px;}
.index-page p{font-size:18px;color:#555;margin-bottom:20px;}
.index-page p::after{content:" 🍲🥗🍞";}
.index-page button{background:#10b981;color:#fff;border:none;padding:12px 20px;font-size:16px;border-radius:8px;cursor:pointer;transition:.3s;animation:bounceButton 2s infinite alternate;}
@keyframes bounceButton{0%{transform:translateY(0);}100%{transform:translateY(-10px);}}
.index-page button:hover{background:#059669;transform:scale(1.1) rotate(-2deg);}
header{padding:20px;background:#4fd0cc;color:#fff;text-align:center;position:relative;overflow:hidden;}
header h1{margin:0;font-size:28px;animation:slideHeader 1s ease-out;}
@keyframes slideHeader{from{transform:translateX(-100%);opacity:0;}to{transform:translateX(0);opacity:1;}}
header .quote{font-style:italic;font-size:16px;margin-top:5px;}
header::after{content:"✨🍽💚";position:absolute;top:10px;right:-50px;font-size:24px;animation:floatEmoji 5s linear infinite;}
@keyframes floatEmoji{0%{right:-50px;top:10px;opacity:0;}50%{right:50%;top:20px;opacity:1;}100%{right:100%;top:10px;opacity:0;}}
.donation-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:15px;padding:20px;}
.card{background:#c7f0e3;border-radius:15px;padding:15px;box-shadow:0 4px 12px rgba(0,0,0,0.1);transition:.3s;position:relative;animation:cardFade .7s ease-in-out;}
@keyframes cardFade{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.card:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 8px 25px rgba(0,0,0,0.25);}
.card h3{margin:0 0 8px;color:#047857;}
.meta{font-size:14px;color:#333;margin-bottom:10px;}
.card::before{content:"🍱";position:absolute;top:-10px;right:-10px;font-size:28px;animation:spinEmoji 3s linear infinite;}
@keyframes spinEmoji{0%{transform:rotate(0);}100%{transform:rotate(360deg);}}
button.accept,button.reject{border:none;padding:8px 12px;border-radius:6px;cursor:pointer;font-weight:600;margin-right:5px;transition:transform .2s;}
button.accept{background:#58beb4;color:white;}button.accept:hover{background:#408690;transform:scale(1.1);}
button.reject{background:#ef4444;color:white;}button.reject:hover{background:#b91c1c;transform:scale(1.1);}
.no-data{text-align:center;background:#bff0df;padding:20px;border-radius:8px;color:#333;font-weight:bold;animation:popIn .5s ease-out;}
@keyframes popIn{from{transform:scale(.8);opacity:0;}to{transform:scale(1);opacity:1;}}
</style>
</head>
<body class="index-page" id="mainBody">
<div class="container" id="welcomePage">
  <h1>Welcome To NGO Portal</h1>
  <p>“Food is meant to be shared — not wasted.”</p>
  <button onclick="showDashboard()">Go to Dashboard</button>
</div>

<div class="dashboard" id="dashboardPage" style="display:none;flex-direction:column;width:100%;">
  <header>
    <h1>NGO Dashboard</h1>
    <p class="quote">“Food is meant to be shared — not wasted.”</p>
  </header>
  <main><div id="donationList" class="donation-list"><p>Loading donations...</p></div></main>
</div>

<script>
function showDashboard(){
  document.getElementById('welcomePage').style.display='none';
  document.getElementById('mainBody').className='dashboard-page';
  document.getElementById('dashboardPage').style.display='flex';
  loadDonations();
}

async function loadDonations(){
  const donationList=document.getElementById("donationList");
  try{
    const res=await fetch("?backend=1");
    const data=await res.json();
    donationList.innerHTML="";
    if(!data.length){
      donationList.innerHTML='<div class="no-data">No donations available.</div>';
      return;
    }
    data.forEach(item=>{
      const card=document.createElement("div");
      card.className="card";
      card.dataset.id=item.pickup_id;
      card.innerHTML=`
        <h3>${item.food_item}</h3>
        <div class="meta">
          <strong>Donor:</strong> ${item.donor_name}<br>
          <strong>Quantity:</strong> ${item.quantity}<br>
          <strong>Expiry:</strong> ${item.expiry_date}<br>
          <strong>Status:</strong> ${item.status}
        </div>
        <button class="accept" onclick="updateStatus(${item.pickup_id}, 'accept')">Accept</button>
        <button class="reject" onclick="updateStatus(${item.pickup_id}, 'reject')">Reject</button>`;
      donationList.appendChild(card);
    });
  } catch(error){
    donationList.innerHTML = <div class="no-data">Could not load donations: ${error.message}</div>;
  }
}

async function updateStatus(pickupId, action){
  if(!confirm(Are you sure you want to ${action} this donation?)) return;
  const res = await fetch("?backend=1", {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({pickup_id: pickupId, action})
  });
  const data = await res.json();
  alert(data.message || data.error);
  if(res.ok){
    document.querySelector([data-id='${pickupId}'])?.remove();
  }
}
</script>
</body>
</html>
