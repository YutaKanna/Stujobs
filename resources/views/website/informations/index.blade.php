@extends('layouts.website')

@section('content')
<div class="containerLg">
    <div class="row justify-content-center">
            <div class="col-xs-12 col-md-12 col-lg-12">
                <div class="boxEffect">
                    <div class="boxEffectHeader">
                        <h3 class="boxEffectTitle">Vos données sur Stujobs :</h3>
                        <p class="paragraphe marginTop">Pour des raisons de confidentialité, vous êtes en droit de demander la copie ou l'effacement de certaines informations personnelles vous concernant.</p>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-md-12 col-lg-12 marginTop">
                <div class="boxEffect">
                    <h3 class="boxTitle centerContent">Effectuer une demande de copie de vos données</h3>
                    <p class="paragraphe">Si vous voulez vérifier si nous possédons des informations à votre sujet, vous pouvez télécharger une copie de celles-ci à tout moment. Si vous souhaitez télécharger vos informations, cliquez sur Télécharger mes informations.</p> 
                    <div class="flexCenter">
                        <a data-toggle="modal" data-target="#modalDownloadData" href="" class="buttonActionLg bgPrimary"><i class="fa fa-download"></i> Télécharger mes informations</a>
                    </div>                   
                </div>
            </div>
            <div class="col-xs-12 col-md-12 col-lg-12 marginTop">
                <div class="boxEffect">
                    <h3 class="boxTitle centerContent">Effectuer une demande de suppression de vos données</h3>
                    <p class="paragraphe">Si vous souhaitez effacer complètement vos données présentes sur Stujobs, nous pouvons nous en charger. Sachez cependant que vous ne pourrez pas récupérer son contenu ou ses informations. Nous vous conseillez d'effectuer avant une demande de copie de vos données. Si vous souhaitez tout de même supprimer celles-ci, cliquez sur Supprimer mes données.</p> 
                    <div class="flexCenter">
                        <a data-toggle="modal" data-target="#modalDeleteAccount" href="" class="buttonActionLg bgDanger"><i class="fa fa-trash"></i> Supprimer mes données</a>   
                    </div>                
                </div>
            </div>
        </div>
    </div>
</div>
    @include('website/informations/delete')
    @include('website/informations/download')
@endsection