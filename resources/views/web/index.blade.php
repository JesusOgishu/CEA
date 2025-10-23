@extends('layout.layout')

@section('content')
<div>
    <div class="container">
        <div class="row">
            <h1>Carousel demo</h1>
            <hr>
            <div id="carouselExampleCaptions" class="carousel slide container">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                    <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
                </div>
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="{{ asset('img/resources/software.webp') }}" class="d-block w-100" alt="Imagen software">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Grupo same</h5>
                            <p>Empresa dedicada a la publicidad y desarrollo.</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="{{ asset('img/resources/tecnologia.jpeg') }}" class="d-block w-100" alt="Imagen tecnología">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Páginas web</h5>
                            <p>Publicidad mediante páginas.</p>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Siguiente</span>
                </button>
            </div>
        </div>

        <div class="row" id="cards" style="margin-top: 3rem;">
            <h1>Demostración de los cards</h1>
            <hr>
        </div>
        <div class="row">
            @for ($i = 0; $i < 4; $i++)
                <div class="col">
                    <div class="card" style="width: 18rem;">
                        <img src="{{ asset('img/resources/tecmi.jpg') }}" class="card-img-top" alt="Card imagen">
                        <div class="card-body">
                            <p style="color: black;" class="card-text">Como podemos ver, los cards pueden tener información en texto e imágenes.</p>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        <div class="row" id="forms" style="margin-top: 3rem;">
            <h1>¡Únete a nosotros!</h1>
            <hr>
        </div>
        <div class="row pb-4">
            <form>
                <div class="mb-3">
                    <label for="Email" class="form-label">Introduzca su E-Mail</label>
                    <input type="email" class="form-control" id="Email" aria-describedby="emailHelp" required>
                    <div id="emailHelp" class="form-text">Tu información será guardada por la empresa.</div>
                </div>
                <div class="mb-3">
                    <label for="Password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="Password" required>
                </div>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </form>
        </div>
    </div>
</div>
@endsection
