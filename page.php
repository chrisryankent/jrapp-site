<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Details</title>
</head>
<body>
    <h1>Loan Details</h1>
    <div id="loan-details"></div>

    <script>
        // Function to fetch loan details
        async function fetchLoanDetails() {
            const apiUrl = "http://localhost/loan-management-system/api.php";
            const requestData = {
                action: "get_loan_info",
                loan_id: "9"
            };

            try {
                const response = await fetch(apiUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams(requestData)
                });

                const data = await response.json();

                if (data.status === "success") {
                    const loanInfo = data.loan_info;
                    document.getElementById("loan-details").innerHTML = `
                        <p><strong>Borrower Name:</strong> ${loanInfo.name}</p>
                        <p><strong>NID:</strong> ${loanInfo.nid}</p>
                        <p><strong>Mobile:</strong> ${loanInfo.mobile}</p>
                        <p><strong>Expected Loan:</strong> ${loanInfo.expected_loan} tk</p>
                        <p><strong>Total Loan:</strong> ${loanInfo.total_loan} tk</p>
                        <p><strong>EMI:</strong> ${loanInfo.emi_loan} tk/month</p>
                        <p><strong>Status:</strong> ${loanInfo.status}</p>
                    `;
                } else {
                    document.getElementById("loan-details").innerHTML = `<p>${data.message}</p>`;
                }
            } catch (error) {
                console.error("Error fetching loan details:", error);
                document.getElementById("loan-details").innerHTML = `<p>Error fetching loan details.</p>`;
            }
        }

        // Call the function to fetch loan details
        fetchLoanDetails();
    </script>
</body>
</html>