<html>
<h1>Products Data</h1>

<table border="1">
    <thead>
        <tr>
            <th>Product Name</th>
            <th>Product Description</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

        @foreach ($products as $item)

        <tr>
            <td>{{ $item->product_name }}</td>
            <td>{{ $item->product_description }}</td>
            <td><a href="#">Delete</a></td>
        </tr>

        @endforeach

    </tbody>
</table>
</html>
