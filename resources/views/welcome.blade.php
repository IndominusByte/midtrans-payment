<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Simple Donation with Midtrans</title>

    <!-- Styles -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"
        crossorigin="anonymous">
    <style>
        html, body {
        background-color: #fff;
        color: #636b6f;
        font-weight: 200;
        height: 100vh;
        margin: 0;
    }
    </style>
</head>

<body>
            <div class="row" style="margin-top:5%;">
                <div class="col-md-4">

                    <!-- Text input-->
                    <div class="form-group">
                        <label class="control-label" for="donor_name">Donor Name</label>
                        <div>
                            <input id="donor_name" name="donor_name" type="text" placeholder="Enter your name.." class="form-control input-md"
                                required="">

                        </div>
                    </div>

                </div>

                <div class="col-md-4">

                    <!-- Text input-->
                    <div class="form-group">
                        <label class="control-label" for="donor_email">Donor Email</label>
                        <div>
                            <input id="donor_email" name="donor_email" type="text" placeholder="Enter your email.." class="form-control input-md"
                                required="">
    
                        </div>
                    </div>
    
                </div>

                <div class="col-md-4">

                    <!-- Select Basic -->
                    <div class="form-group">
                        <label class="control-label" for="donation_type">Type</label>
                        <div>
                            <select id="donation_type" name="donation_type" class="form-control">
                                <option value="infak_kemanusiaan">Infak Kemanusiaan</option>
                                <option value="infak_pendidikan">Infak Pendidikan</option>
                                <option value="infak_kesehatan">Infak Kesehatan</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <div class="row">
                <div class="col-md-6">

                    <!-- Prepended text-->
                    <label for="">Amount</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">Rp</span>
                        </div>
                        <input id="amount" name="amount" class="form-control" placeholder="" type="number" min="10000" max="999999999" required="">
                    </div>

                </div>
                <div class="col-md-6">

                    <!-- Textarea -->
                    <div class="form-group">
                        <label class="control-label" for="note">Note (Optional)</label>
                        <div>
                            <textarea class="form-control" id="note" name="note"></textarea>
                        </div>
                    </div>

                </div>
            </div>

            <button id="submit" class="btn btn-success">Submit</button>

        </form>

        <br>

        <table class="table table-striped" id="list">
            <tr>
                <th>Order id</th>
                <th>Donor Name</th>
                <th>Amount</th>
                <th>Donation Type</th>
                <th>Status</th>
                <th>Payment type</th>
                <th>Bank</th>
                <th>Virtual Account</th>
                <th>Cancel Transaction</th>
            </tr>
            @foreach ($donations as $donation)
            <tr>
                <td><code>{{ $donation->order_id }}</code></td>
                <td>{{ $donation->donor_name }}</td>
                <td>Rp. {{ number_format($donation->amount) }},-</td>
                <td>{{ ucwords(str_replace('_', ' ', $donation->donation_type)) }}</td>
                <td>{{ ucfirst($donation->status) }}</td>
                <td>{{$donation->payment_type}}</td>
                <td>{{$donation->bank}}</td>
                <td>{{$donation->va_number}}</td>
                <td>

                  <form action="{{url("/donation/$donation->order_id/cancel")}}" method="post">
                    @csrf
                    <button type="submit">Cancel</button>
                  </form>
                </td>
            </tr>
            @endforeach
            <tr>
                <td colspan="9">{{ $donations->links() }}</td>
            </tr>
        </table>

    </div>
    <script
        src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
        crossorigin="anonymous"></script>
    <script src="{{ !config('services.midtrans.isProduction') ? 'https://app.sandbox.midtrans.com/snap/snap.js' : 'https://app.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.clientKey') }}"></script>
    <script>
      $('#submit').click(function(e){
        $.post("{{ route('donation.store') }}",
        {
            _method: 'POST',
            _token: '{{ csrf_token() }}',
            amount: $('input#amount').val(),
            note: $('textarea#note').val(),
            donation_type: $('select#donation_type').val(),
            donor_name: $('input#donor_name').val(),
            donor_email: $('input#donor_email').val(),
        },
        function (data, status) {
            snap.pay(data.snap_token, {
                // Optional
                onSuccess: function (result) {
                    location.reload();
                },
                // Optional
                onPending: function (result) {

 var bank_value,va_number_value;
 if(result['permata_va_number'] !== undefined){
   bank_value = 'permata';
   va_number_value = result['permata_va_number'];
 }
 else if(result['bill_key'] !== undefined){
   bank_value = 'mandiri';
   va_number_value = result['bill_key'];
 }
 else{
   bank_value = result['va_numbers']['0']['bank'];
   va_number_value = result['va_numbers']['0']['va_number'];
 }

jQuery(document).ready(function(){
    e.preventDefault();
    $.ajaxSetup({
       cache: false,
       headers: {
           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
       }
   });
    jQuery.ajax({
      url: "{{ route('donation.save') }}",
       method: 'post',
       data: {
         amount: $('input#amount').val(),
         note: $('textarea#note').val(),
         donation_type: $('select#donation_type').val(),
         donor_name: $('input#donor_name').val(),
         donor_email: $('input#donor_email').val(),
         order_id:result['order_id'],
         payment_type:result['payment_type'],
         pdf_url:result['pdf_url'],
         bank:bank_value,
         va_number:va_number_value,
       },
       success: function(result){
       if(result['success'] == true)
          location.reload();
    }});
  });
                },
                // Optional
                onError: function (result) {
                    location.reload();
                }
            });
        });
        return false;
});
    </script>
</body>
</html>
