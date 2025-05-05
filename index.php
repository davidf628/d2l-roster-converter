<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csvData'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students.csv"');
    echo $_POST['csvData'];
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>D2L Student Data Parser</title>
    <style>
        textarea { width: 100%; height: 200px; }
        .container { display: flex; gap: 20px; }
        .box { flex: 1; }
        .options { margin-top: 10px; }
        label { display: block; margin: 4px 0; }
    </style>
</head>
<body>
    <h1>D2L Student Data Parser</h1>

    <div class="container">
        <!-- Input area -->
        <div class="box">
            <h3>Paste D2L Data</h3>
            <textarea id="rawInput" placeholder="Paste copied D2L data here..."></textarea>
        </div>

        <!-- Output area -->
        <div class="box">
            <h3>Parsed Students <span id="counter">(0 found)</span></h3>
            <textarea id="processedOutput" readonly></textarea>
        </div>
    </div>

    <!-- Options -->
    <div class="options">
        <h3>Export Options</h3>
        <label><input type="checkbox" value="name" checked class="field format-option"> Include Name</label>
        <label><input type="checkbox" value="id" checked class="field format-option"> Include Student ID</label>
        <label><input type="checkbox" value="email" checked class="field format-option"> Include Email</label>

        <label>Name Format:
            <select id="nameFormat" class="format-option">
                <option value="lastFirst">Last, First</option>
                <option value="firstLast">First Last</option>
            </select>
        </label>

        <label>Case Format:
            <select id="caseFormat" class="format-option">
                <option value="normal">First Caps</option>
                <option value="lower">lower case</option>
                <option value="upper">UPPER CASE</option>
            </select>
        </label>

        <label>Field Separator:
            <select id="separator" class="format-option">
                <option value=",">Comma</option>
                <option value=";">Semicolon</option>
                <option value="\t">Tab</option>
            </select>
        </label>

        <button onclick="copyToClipboard()">Copy to Clipboard</button>

        <form method="post" style="display: inline;">
            <input type="hidden" name="csvData" id="csvData">
            <button type="submit" onclick="prepareCSV()">Download CSV</button>
        </form>

    </div>

    <script>
        const rawInput = document.getElementById('rawInput');
        const output = document.getElementById('processedOutput');
        const counter = document.getElementById('counter');
        let parsedStudents = [];

        rawInput.addEventListener('input', parseData);

        // Re-render output for any format option change
        document.querySelectorAll('.format-option').forEach(el => {
            el.addEventListener('change', () => {
                renderOutput(parsedStudents); 
            });
        });
        
        function parseData() {
            const lines = rawInput.value.split('\n').map(line => line.trim()).filter(Boolean);
            const students = [];

            for (const line of lines) {

                if (line.startsWith('View Profile for')) {
                    const parts = line.split('\t');
                    const fullName = parts[0].replace('View Profile for', '').trim();
                    const rawName = parts[1] || '';
                    let [last, first] = rawName.split(',').map(s => s.trim());
                    first = first.replace('is online', '');
                    first = first.replace(fullName, '').trim();

                    const id = parts.find(part => /^\d{7,9}$/.test(part));
                    const email = parts.find(part => /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/.test(part));
                    const isStudent = parts.some(part => /student/i.test(part));

                    if (isStudent && first && last && id && email) {
                        students.push({ first, last, id, email });
                    }
                }

            }
            parsedStudents = students;
            renderOutput(students);
        }
        
        function formatName({ first, last }) {
            const nameFormat = document.getElementById('nameFormat').value;
            let name = '';

            if (nameFormat === 'lastFirst') {
                name = `${last}, ${first}`;
            } else {
                name = `${first} ${last}`;
            }

            const caseFormat = document.getElementById('caseFormat').value;
            if (caseFormat === 'upper') return name.toUpperCase();
            if (caseFormat === 'lower') return name.toLowerCase();
            return name.replace(/\b\w/g, l => l.toUpperCase());
        }

        function renderOutput(students) {
            counter.textContent = `(${students.length} found)`;
            const fields = [...document.querySelectorAll('.field:checked')].map(el => el.value);
            const sep = document.getElementById('separator').value.replace('\\t', '\t');

            const lines = students.map(student => {
                const parts = [];
                if (fields.includes('name')) parts.push(formatName(student));
                if (fields.includes('id')) parts.push(student.id);
                if (fields.includes('email')) parts.push(student.email);
                return parts.join(sep);
            });

            if (sep === ';') {
                output.value = lines.join(';');
            } else {
                output.value = lines.join('\n');
            }
        }

        function copyToClipboard() {
            navigator.clipboard.writeText(output.value)
                .then(() => alert('Copied to clipboard!'))
                .catch(() => alert('Copy failed.'));
        }

        function prepareCSV() {
            const sep = ',';
            const lines = output.value.split('\n').map(line => line.split(sep));
            const csvLines = lines.map(row => row.map(field => `"${field}"`).join(','));
            document.getElementById('csvData').value = csvLines.join('\n');
        }
        
    </script>
</body>
</html>