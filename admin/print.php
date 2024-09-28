<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'includes/session.php';

// Function to generate rows with election results
function generateRow($conn) {
    $contents = '';
    $sql = "SELECT * FROM positions ORDER BY priority ASC";
    $query = $conn->query($sql);
    
    while ($row = $query->fetch_assoc()) {
        $id = $row['id'];
        $contents .= '
            <tr class="position-header">
                <td colspan="3" align="center"><b>' . $row['description'] . '</b></td>
            </tr>
            <tr class="table-subheader">
                <td><b>Candidates</b></td>
                <td><b>Votes</b></td>
                <td><b>Percentage</b></td>
            </tr>
        ';

        // Get the total votes for the position
        $totalVotesSql = "SELECT COUNT(*) as total_votes FROM votes WHERE candidate_id IN (SELECT id FROM candidates WHERE position_id = '$id')";
        $totalVotesResult = $conn->query($totalVotesSql);
        $totalVotesRow = $totalVotesResult->fetch_assoc();
        $totalVotes = $totalVotesRow['total_votes'];

        $sql = "SELECT * FROM candidates WHERE position_id = '$id' ORDER BY lastname ASC";
        $cquery = $conn->query($sql);
        
        while ($crow = $cquery->fetch_assoc()) {
            $sql = "SELECT * FROM votes WHERE candidate_id = '" . $crow['id'] . "'";
            $vquery = $conn->query($sql);
            $votes = $vquery->num_rows;

            // Calculate percentage
            $percentage = $totalVotes > 0 ? ($votes / $totalVotes) * 100 : 0;

            $contents .= '
                <tr class="candidate-row">
                    <td>' . $crow['lastname'] . ", " . $crow['firstname'] . '</td>
                    <td>' . $votes . '</td>
                    <td>' . number_format($percentage, 2) . '%</td>
                </tr>
            ';
        }

        // Display total votes after candidates
        $contents .= '
            <tr class="summary-row">
                <td colspan="2"><strong>Total Votes:</strong></td>
                <td>' . $totalVotes . '</td>
            </tr>
        ';
    }
    return $contents;
}

// Get election title and total number of voters
$parse = parse_ini_file('config.ini', FALSE, INI_SCANNER_RAW);
$title = $parse['election_title'];

// Get total number of voters
$totalVotersSql = "SELECT COUNT(*) as total_voters FROM voters"; // Adjust the table name as necessary
$totalVotersResult = $conn->query($totalVotersSql);
$totalVotersRow = $totalVotersResult->fetch_assoc();
$totalVoters = $totalVotersRow['total_voters'];

// Get total number of voters who voted
$votedVotersSql = "SELECT COUNT(DISTINCT id) as voted_voters FROM votes"; // Adjust the column and table name as necessary
$votedVotersResult = $conn->query($votedVotersSql);
$votedVotersRow = $votedVotersResult->fetch_assoc();
$votedVoters = $votedVotersRow['voted_voters'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
        }

        .btn {
            display: inline-block;
            margin: 20px;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #4CAF50;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            color: #4CAF50;
        }

        .modal-header h4 {
            margin: 5px 0 10px;
            font-size: 18px;
            color: #666;
        }

        .modal-body {
            max-height: 60vh;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .position-header {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .table-subheader {
            background-color: #4CAF50;
            color: #fff;
        }

        .candidate-row:nth-child(even) {
            background-color: #f9f9f9;
        }

        .summary-row {
            background-color: #e7f3fe; /* Light blue background for summary */
            font-weight: bold;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .print-btn {
            background-color: #2196F3;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .print-btn:hover {
            background-color: #0b7dda;
        }

        @media print {
            .close, .print-btn, .btn {
                display: none; /* Hide close button and print button in print */
            }
            .modal {
                background-color: #fff; /* Background should be white for printing */
            }
        }
    </style>
</head>
<body>

<!-- Button to trigger modal -->
<button class="btn" id="showModal">Show Election Results</button>

<!-- Modal Structure -->
<div id="resultsModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <div class="modal-header">
            <h2><?php echo $title; ?></h2>
            <h4>Tally Result</h4>
        </div>
        <div class="modal-body">
            <p><strong>Total Number of Voters:</strong> <?php echo $totalVoters; ?></p>
            <p><strong>Number of Voters Who Voted:</strong> <?php echo $votedVoters; ?></p>
            <table>
                <?php echo generateRow($conn); ?>
            </table>
        </div>
        <button class="print-btn" id="printButton">Print Results</button>
    </div>
</div>

<script>
    // JavaScript to handle modal display and printing
    const modal = document.getElementById('resultsModal');
    const showModalBtn = document.getElementById('showModal');
    const closeModalBtn = document.getElementById('closeModal');
    const printButton = document.getElementById('printButton');

    showModalBtn.onclick = function() {
        modal.style.display = 'block';
    }

    closeModalBtn.onclick = function() {
        modal.style.display = 'none';
    }

    printButton.onclick = function() {
        window.print();
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

</body>
</html>
