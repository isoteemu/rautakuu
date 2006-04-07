import java.util.*;
import java.io.*;

public class ArpojaMyydaan {
	int arpoja;

	public static void main(String[] args){
		System.out.println("T‰m‰ ohjelman on tehnyt Raine Liukko.\n");
		System.out.println("Tervetuloa arpojen myyntiohjelmaan. T‰ll‰ ohjelmalla voit hallinnoida" +
    		" arpojen myynti‰.\n" +
    		"\n" +
    		"1. Myy arpoja\n" +
    		"2. Arpojen m‰‰ritykset\n" +
    		"0. Lopetus");
		Valitse();
	}
	public static void Valitse(){
            try
            {
	    String valinta;
	    BufferedReader syote = new BufferedReader(new InputStreamReader(System.in));
	    System.out.print("Syˆt‰ valinta > ");
	    valinta = syote.readLine();
	    int valinta2 = Integer.parseInt(valinta);
	    switch(valinta2)
	    {
	    case 1:
	    	MyyArpoja();
	    	break;
	    	
	    case 2:
	    	ArpojenMaaritykset();
	    	break;
	    	
	    case 0:
	    	System.exit(0);
	    	
	    	default:
	    	System.out.println("Valitse joku luettelon vaihtoehdoista!");
	    	Valitse();
	    }
	    
	    }
	    catch(IOException e)
	    {
	    	System.out.println("Virheellinen syˆte! Yrit‰ uudelleen.");
                Valitse();
	    }
	    catch(NumberFormatException e)
	    {
	    	System.out.println("Syˆtteen pit‰‰ olla numero! Syˆt‰ valintasi uudelleen.");
                Valitse();
	    }
		
	}
	
	public static void MyyArpoja(){
		System.out.println("T‰‰lt‰ voit suorittaa arpojen myymisen. Myynneist‰ pidet‰‰n tilastoa tilastot");
	}
	
	public static void ArpojenMaaritykset(){
		System.out.println("T‰‰lt‰ voit m‰‰ritell‰ arpojen hinnat ja paljonko niit‰ myyd‰‰n.\n");
		System.out.println("1. M‰‰rit‰ arpojen m‰‰r‰" +
				"\n2. M‰‰rit‰ arpojen hinta" +
				"\n0. Takaisin p‰‰valikkoon\n");
                try
                {
                String valinta;
                BufferedReader syote = new BufferedReader(new InputStreamReader(System.in));
                System.out.print("Syˆt‰ valinta > ");
                valinta = syote.readLine();
                int valinta2 = Integer.parseInt(valinta);
                
                switch(valinta2)
                {
                    case 1:
                        System.out.println("\nM‰‰ritet‰‰n ensin tiedostolle nimi, johon m‰‰ritet‰‰n arpojen m‰‰r‰.");
                        String TNimi;
                        BufferedReader tiedostoNimi = new BufferedReader(new InputStreamReader(System.in));
                        System.out.print("Tiedoston nimi: ");
                        TNimi = tiedostoNimi.readLine();
                        String tiedosto = TNimi + ".txt";
                        
                        String ArpojenMaara;
                        BufferedReader arvat = new BufferedReader(new InputStreamReader(System.in));
                        System.out.print("Myyt‰vien arpojen m‰‰r‰: ");
                        ArpojenMaara = arvat.readLine();
                        FileOutputStream ArpaMaaraTallennus = new FileOutputStream(tiedosto);
                        ObjectOutputStream TallennusVirta = new ObjectOutputStream(ArpaMaaraTallennus);
                        TallennusVirta.writeObject(ArpojenMaara);
                        TallennusVirta.flush();
                        TallennusVirta.close();
                        ArpaMaaraTallennus.close();                        
                        break;
                        
                    case 2:
                        System.out.println("Kuinka paljon on myyt‰vi‰ arpoja?");
                        break;
                }
                }
                catch(IOException e)
                {
                    System.out.println("Virheellinen syˆte! Yrit‰ uudelleen.");
                    ArpojenMaaritykset();
                }
                catch(NumberFormatException e)
	    {
	    	System.out.println("Syˆtteen pit‰‰ olla numero! Syˆt‰ valintasi uudelleen.");
                ArpojenMaaritykset();
	    }
	}
	

}
