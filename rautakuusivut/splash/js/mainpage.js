    k=document;v=Date;x=false;z=Array;af=Math.floor;ag=RegExp;b=new z(8);s=new z("null","rautakuu","horde","cs","tao","mantis","binocular");aa=new z(11);ab=10;t=0;u=0;n=0;o=new v();h=5;m=385;c=0;w=x;var title;var firstHoverOccurred=x;m=385;p=0;function d(ac){c=ac;o=new v();setTimeout("gidle()",20);}function e(ac){c=0;w=x;o=new v();setTimeout("gidle()",20);}function ae(){for(var j=1;j<b.length;j++){b[j]=35}title=k.getElementById('imageTitle');for(i=0;i<b.length;i++){aa[i]=new Image();aa[i].src="img/"+s[i+1]+".gif"}setTimeout("gidle()",20);}function gidle(){var l=0;for(var i=1;i<b.length;i++){var imagename="image"+i;var imageElem=k.getElementById(imagename);if(c!=i){if(b[i]>35){b[i]-=h;if(b[i]<=35){b[i]=35;imageElem.src="img/"+s[i]+".gif"}imageElem.width=b[i];imageElem.height=b[i];if(c==0){var g=af(255-255*(b[i]-35)/35);title.style.color="rgb("+g+","+g+","+g+")"}p=1}l+=b[i]}}if(c!=0&&b[c]<70){imagename="image"+c;imageElem=k.getElementById(imagename);if(w==x){w=true;if(c<4){var y=240-c*70;title.innerHTML=k.getElementById(imagename).alt+'<img src="img/spacer.gif" width="'+y+'" height="1">'}else{var y=(c-4)*70+35;title.innerHTML='<img src="img/spacer.gif" width="'+y+'" height="1">'+k.getElementById(imagename).alt}}b[c]+=h;p=1;if(b[c]>70){b[c]=70}l+=b[c];if(l<m){b[c]+=m-l;if(b[c]>70){b[c]=70}l=m}var g=af(255-255*(b[c]-35)/35);title.style.color="rgb("+g+","+g+","+g+")";imageElem.width=b[c];imageElem.height=b[c];k.getElementById(imagename).src="img/"+s[c]+".gif"}m=l;var ad=new v();ab=ad.getTime()-o.getTime();o=ad;t+=ab;u++;n=t/u;h=5;if(u>4){if(n>30){h=10}if(n>60){h=15}if(n>90){h=20}}if(p){setTimeout("gidle()",20);p=0}}

    var lentavahollantilainen = '<?= lentavahollantilainen();?>';

    function clearVal(fuckmegently) {
      if(fuckmegently.value == lentavahollantilainen) {
        fuckmegently.value="";
        fuckmegently.style.color="#000000";
      }
    }
