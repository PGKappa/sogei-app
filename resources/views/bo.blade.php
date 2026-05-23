<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN BACKOFFICE</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error{
            color:red;
        }
    </style>
</head>
<body>
    <div id="login">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">Login</div>
                        <div class="card-body">
                            <form method="POST" action=""><input type="hidden" name="_token"
                                    value="Q3I20iIqvHO4i4Ys891G2GILWiAcygIiamHwUu4n">
                                <div class="form-group row"><label for="username"
                                        class="col-sm-4 col-form-label text-md-right">Username</label>
                                    <div class="col-md-6"><input id="username" type="username" name="username" value=""
                                            required="required" autofocus="autofocus" class="form-control"></div>
                                </div>
                                <div class="form-group row"><label for="password"
                                        class="col-md-4 col-form-label text-md-right">Password</label>
                                    <div class="col-md-6"><input id="password" type="password" name="password"
                                            required="required" class="form-control"></div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-6 offset-md-4 remember-container">
                                        <div class="form-check"><input type="checkbox" name="remember" id="remember"
                                                class="form-check-input"> <label for="remember" class="form-check-label">
                                                Remember Me
                                            </label></div>
                                    </div>
                                </div>
                                <div class="form-group row mb-0">
                                    <div class="col-md-8 offset-md-4 login-container"><button type="submit"
                                            class="btn btn-primary login-button">
                                            Login
                                        </button></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="page">
        <div class="header">
            logout
        </div>
        <div class="card">
            <div class="card-header">Overrounds</div>
            <div class="card-body jackpot-body">
                <form>
                    <div class="form-row align-items-center justify-content-center">
                        <div class="col-auto" style="width: 95%;">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend" style="min-width: 50%;">
                                    <div class="input-group-text" style="width: 100%;">Vincente</div>
                                </div><input type="number" size="5" class="form-control" id="overround_winner" data-min="80" data-max="90"
                                    style="min-width: 50%; white-space: nowrap;">
                            </div>
                        </div>
                        <div class="col-auto" style="width: 95%;">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend" style="min-width: 50%;">
                                    <div class="input-group-text" style="width: 100%;">Piazzato 1&deg; 2&deg;</div>
                                </div><input type="number" size="5" class="form-control" id="overround_placed" data-min="80" data-max="90"
                                    style="min-width: 50%; white-space: nowrap;">
                            </div>
                        </div>
                        <div class="col-auto" style="width: 95%;">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend" style="min-width: 50%;">
                                <div class="input-group-text" style="width: 100%;">Piazzato 1&deg; 2&deg; 3&deg;</div>
                                </div><input type="number" size="5" class="form-control" id="overround_show" data-min="80" data-max="90"
                                    style="min-width: 50%; white-space: nowrap;">
                            </div>
                        </div>
                        <div class="col-auto" style="width: 95%;">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend" style="min-width: 50%;">
                                    <div class="input-group-text" style="width: 100%;">Accoppiata in ordine</div>
                                </div><input type="number" size="5" class="form-control" id="overround_exacta" data-min="60" data-max="90"
                                    style="min-width: 50%; white-space: nowrap;">
                            </div>
                        </div>
                        <div class="col-auto" style="width: 95%;">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend" style="min-width: 50%;">
                                    <div class="input-group-text" style="width: 100%;">Accoppiata a girare</div>
                                </div><input type="number" size="5" class="form-control" id="overround_quinella" data-min="60" data-max="90"
                                    style="min-width: 50%; white-space: nowrap;">
                            </div>
                        </div>
                        <div class="col-auto" style="width: 95%;">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend" style="min-width: 50%;">
                                    <div class="input-group-text" style="width: 100%;">Trio in ordine</div>
                                </div><input type="number" size="5" class="form-control" id="overround_trifecta" data-min="60" data-max="90"
                                    style="min-width: 50%; white-space: nowrap;">
                            </div>
                        </div>
                        <div class="col-auto" style="width: 95%;">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend" style="min-width: 50%;">
                                    <div class="input-group-text" style="width: 100%;">Trio a girare</div>
                                </div><input type="number" size="5" class="form-control" id="overround_trifecta_o" data-min="60" data-max="90"
                                    style="min-width: 50%; white-space: nowrap;">
                            </div>
                        </div>
                        <div class="col-auto" style="width: 95%;">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend" style="min-width: 50%;">
                                    <div class="input-group-text" style="width: 100%;">Under / Over</div>
                                </div><input type="number" size="5" class="form-control" id="overround_underover" data-min="80" data-max="90"
                                    style="min-width: 50%; white-space: nowrap;">
                            </div>
                        </div>
                        <div class="col-auto" style="width: 95%;">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend" style="min-width: 50%;">
                                    <div class="input-group-text" style="width: 100%;">Pari / Dispari</div>
                                </div><input type="number" size="5" class="form-control" id="overround_oddeven" data-min="80" data-max="90"
                                    style="min-width: 50%; white-space: nowrap;">
                            </div>
                        </div>
                    </div>
                    <div class="form-row align-items-center apply-button"><input type="button" class="btn btn-primary"
                            id="apply-overround-info" value="Apply" style="margin: auto;"></div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        var logged=false;
        var values = {
            "overround_winner":80,
            "overround_placed":80,
            "overround_show":80,
            "overround_exacta":60,
            "overround_quinella":60,
            "overround_trifecta":60,
            "overround_trifecta_o":60,
            "overround_underover":80,
            "overround_oddeven":80

        }
        $(document).ready(function(){
            $('#page').hide();
            $('#login').show();


            var storageValues = JSON.parse(localStorage.getItem('overround_data'));
            if(!storageValues){
                storageValues = values;
                localStorage.setItem('overround_data',JSON.stringify(storageValues));
            }

            Object.keys(storageValues).forEach((v)=>{
                $('#'+v).val(storageValues[v]);
            })



            $('button[type="submit"]').click(function (e) {
                e.preventDefault();
                e.stopPropagation();

                var username = $('input#username').val();
                var password = $('input#password').val();

                if (username == "root" && password == "root") {
                    logged = true;
                    $('#login').hide();
                    $('#page').show();
                }

            });

            $('input[type="number"]').change(function(e){
                console.log("valore cambiato");
                var a = $(this).val();
                if(a < $(this).data('min') || a > $(this).data('max')){
                    console.log("error");
                    $(this).addClass("error");
                    $(this).closest("div.input-group").find('.invalid').remove();
        $(this).closest("div.input-group").addClass("error").append('<div class="error invalid - feedback">Valore Errato. Rtp consentito nel range '+$(this).data('min')+'- '+$(this).data('max')+'</div >');
                        $('#apply-overround-info').attr('disabled','disables');
                    } else {
                    $(this).closest("div.input-group").find('.invalid').remove();
                     $(this).removeClass("error");
                     $('#apply-overround-info').removeAttr('disabled');
                }
            });

            $('#apply-overround-info').click(function(e){
                e.preventDefault();
                console.log($('input[type="number"]'));
                 $('input[type="number"]').each(function (e){
                    storageValues[$(this).attr('id')]=$(this).val();
                 });

                 localStorage.setItem('overround_data',JSON.stringify(storageValues));


            })

            
        })
        
    </script>
</body>
</html>

