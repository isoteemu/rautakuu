import java.util.*;
import java.io.*;

public class ArpojaMyydaan {
	int arpoja;

	public static void main(String[] args){
		System.out.println("T‰m‰ ohjelman on tehnyt Raine Liukko.\n\n\n\n");
		System.out.println("Tervetuloa arpojen myyntiohjelmaa. T‰ll‰ ohjelmalla voit hallinnoida" +
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
	    System.out.print("Syˆt‰ valita > ");
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
	    	System.out.println("Virheellinen syˆte!");
	    }
	    catch(NumberFormatException e)
	    {
	    	System.out.println("Syˆtteen pit‰‰ olla numero!");
	    }
		
	}
	
	public static void MyyArpoja(){
		System.out.println("Myy arpoja");
	}
	
	public static void ArpojenMaaritykset(){
		System.out.println("T‰‰lt‰ voit m‰‰ritell‰ arpojen hinnat ja paljonko niit‰ myyd‰‰n.\n");
		System.out.println("1. M‰‰rit‰ arpojen m‰‰r‰" +
				"\n2. M‰‰rit‰ arpojen hinta" +
				"\n0. Takaisin p‰‰valikkoon");
		
		
	}
	

}
