<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración DNS</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 20px;
            background: #f1f5f9;
            color: #1e293b;
            font-family: Arial, sans-serif;
        }

        body > h2,
        body > h3,
        body > form,
        body > table,
        body > p {
            width: min(100%, 1000px);
            margin-left: auto;
            margin-right: auto;
        }

        h2 {
            margin-top: 0;
            margin-bottom: 24px;
            color: #12304a;
            font-size: 27px;
        }

        h3 {
            margin-top: 32px;
            margin-bottom: 14px;
            color: #12304a;
        }

        body > form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            padding: 18px;
            background: white;
            border: 1px solid #dbe4ee;
            border-radius: 12px;
        }

        label {
            color: #475569;
            font-weight: 600;
        }

        input,
        select {
            min-height: 40px;
            padding: 8px 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: white;
            color: #1e293b;
            font: inherit;
        }

        input:focus,
        select:focus {
            outline: 2px solid #99d5d0;
            border-color: #16877e;
        }

        button {
            min-height: 40px;
            padding: 9px 15px;
            border: 0;
            border-radius: 7px;
            background: #087f75;
            color: white;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #06665f;
        }

       a {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 7px;
    background: #087f75;
    color: white;
    font-weight: 600;
    text-decoration: none;
}

a:hover {
    background: #06665f;
    text-decoration: none;
}

        table {
            overflow: hidden;
            border-collapse: collapse;
            background: white;
            border: 1px solid #dbe4ee;
            border-radius: 12px;
        }

        th,
        td {
            padding: 13px 15px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        th {
            background: #e8f2f3;
            color: #12304a;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        tr:hover td {
            background: #f8fbfc;
        }

        td form {
            margin: 0;
        }

        td button {
            min-height: 32px;
            padding: 6px 10px;
            background: #b42332;
            font-size: 14px;
        }

        td button:hover {
            background: #8f1c28;
        }

        @media (max-width: 700px) {
            body {
                padding: 24px 12px;
            }

            body > table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>