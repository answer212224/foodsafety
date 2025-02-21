<!DOCTYPE html>
<html>

<head>
    <title>巡檢平台提醒</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 20px;
        }

        h2 {
            color: #0056b3;
            font-size: 24px;
        }

        p {
            font-size: 16px;
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        a {
            color: #0056b3;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <h2>巡檢平台提醒</h2>
    <p>您好，提醒您明日有一則巡檢稽核任務，請務必完成。</p>
    <p>巡檢稽核相關資訊如下:</p>
    <table>
        <thead>
            <tr>
                <th>地點</th>
                <th>任務類別</th>
                <th>任務日期</th>
                <th>同仁</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $shop }}</td>
                <td>{{ $category }}</td>
                <td>{{ $task_date }}</td>
                <td>{{ $user_name }}</td>
            </tr>
        </tbody>
    </table>

    <p>請至巡檢平台查看</p>
    <p>巡檢平台連結: <a href="{{ $url }}" target="_blank">巡檢平台</a></p>
    <p>Good luck!</p>
</body>

</html>
