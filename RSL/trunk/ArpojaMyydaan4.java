import java.util.*;
import java.io.*;

public class ArpojaMyydaan
{
	public static void main(String[] args){
		valikko();
	}
	public static void valikko(){
		System.out.println("\n\nTämän ohjelman on tehnyt Raine Liukko" +
				"\nrsl@rautakuu.org\n");
		System.out.println("1. Uusi myyntierä" +
				"\n2. Jatka vanhaa myyntierää" +
				"\n0. Lopeta\n\n");
		try{
			BufferedReader syote = new BufferedReader(new InputStreamReader(System.in));
			String valinta;
			System.out.print("Valintasi > ");
			valinta = syote.readLine();
			int muunto = Integer.parseInt(valinta);
			
			switch(muunto)
			{
				case 0:
					System.out.println("\n\nEXIT\n\n");
					System.exit(0);
				
				case 1:
					uusiProjekti();
					break;
				
				case 2:
					wanhaProjekti();
					break;
					
				default:
					System.out.println("Valitse joku luettelon vaihtoehdoista!");
					valikko();
			}
		}
		catch(IOException e)
		{
			System.out.println("Virhe syötteessä. Yritä uudelleen.");
			valikko();
		}
		catch(NumberFormatException e)
		{
			System.out.println("Syötte ei saa sisältää numeroita. Yritä uudelleen.");
			valikko();
		}
	}
// 	Uusi myyntierä
	public static void uusiProjekti(){
		try{			
			BufferedReader syote = new BufferedReader(new InputStreamReader(System.in));
			String nimi1;
			String maara1;
			String hinta1;
			int maara2;
			double hinta2;
			System.out.print("Myyntierän nimi > ");
			projekti tiedot;
			nimi1 = syote.readLine();
			System.out.print("Arpojen määrä > ");
			maara1 = syote.readLine();
			maara2 = Integer.parseInt(maara1);
			System.out.print("Yksi arpa maksaa > ");
			hinta1 = syote.readLine();
			hinta2 = Double.parseDouble(hinta1);
			tiedot = new projekti(nimi1, maara2, hinta2);
// 			Tallennetaan projektin tiedot
			projektinTallennus uPtallennus;
			uPtallennus = new projektinTallennus(nimi1, nimi1, maara1, hinta1);
			System.out.println(uPtallennus.getFile1() + " ... Tallennettu");
			System.out.println(uPtallennus.getFile2() + " ... Tallennettu");
			System.out.println(uPtallennus.getFile3() + " ... Tallennettu");
		}
		catch(IOException e)
		{
			System.out.println("Virhe syötteessä. Yritä uudelleen.");
			uusiProjekti();
		}
	}
// 	Wanha myyntierä
	public static void wanhaProjekti(){
		
	}
}

class arpa{
	private int Maara;
	private double Hinta;
	
	public arpa(int maaraOn, double hintaOn){
		Maara = maaraOn;
		Hinta = hintaOn;
	}
	
	public void setMaara(int arpojenMaara){
		Maara = arpojenMaara;
	}
	
	public int getMaara(){
		return Maara;
	}
	
	public void setHinta(double arpojenHinta){
		Hinta = arpojenHinta;
	}
	
	public double getHinta(){
		return Hinta;
	}	
}
// Myyntierän tiedot
class projekti{
	private String Nimi;
	private int Maara;
	private double Hinta;
	
	public projekti(String nimiOn, int maaraOn, double hintaOn){
		Nimi = nimiOn;
		Maara = maaraOn;
		Hinta = hintaOn;
	}
	
	public void setNimi(String nimiOn){
		Nimi = nimiOn;
	}
	
	public String getNimi(){
		return Nimi;
	}
	
	public void setMaara(int maaraOn){
		Maara = maaraOn;
	}
	
	public int getMaara(){
		return Maara;
	}
	
	public void setHinta(double hintaOn){
		Hinta = hintaOn;
	}
	
	public double getHinta(){
		return Hinta;
	}
	
}

class projektinTallennus{
	private String nimi1;
	private String file1;
	private String nimi2;
	private String file2;
	private String nimi3;
	private String file3;
	FileOutputStream tallennus;
	ObjectOutputStream tallennusVirta;
	
	public projektinTallennus(String nameIs, String file1is, String file2is, String file3is){
		nimi1 = nameIs + ".dat";
		file1 = file1is;
		nimi2 = nameIs + "Maara.dat";
		file2 = file2is;
		nimi3 = nameIs + "Hinta.dat";
		file3 = file3is;
	}
	
	public void setFile1(String nameIs, String file1is){
			nimi1 = nameIs + ".dat";
			file1 = file1is;
	}
	
	public String getFile1(){
		try{
			tallennus = new FileOutputStream(nimi1);
			tallennusVirta = new ObjectOutputStream(tallennus);
			tallennusVirta.writeObject(file1);
			tallennusVirta.flush();
			tallennusVirta.close();
			tallennus.close();
		}
		catch(FileNotFoundException e){
			System.out.println("Tiedostoa ei löydy.");
		}
		catch(IOException e){
			System.out.println("Virhe Syötteessä.");
		}
		return file1;
	}
		
	public void setFile2(String nameIs, String file2is){
			nimi2 = nameIs + "Maara.dat";
			file2 = file2is;
	}
	public String getFile2(){
		try{
			tallennus = new FileOutputStream(nimi2);
			tallennusVirta = new ObjectOutputStream(tallennus);
			tallennusVirta.writeObject(file2);
			tallennusVirta.flush();
			tallennusVirta.close();
			tallennus.close();
		}
	catch(FileNotFoundException e){
		System.out.println("Tiedostoa ei löydy.");
	}
	catch(IOException e){
		System.out.println("Virhe Syötteessä.");
	}

		return file2;
	}
	
	public void setFile3(String nameIs, String file3is){
			nimi3 = nameIs + "Hinta.dat";
			file3 = file3is;
	}
	public String getFile3(){
		try{
			tallennus = new FileOutputStream(nimi3);
			tallennusVirta = new ObjectOutputStream(tallennus);
			tallennusVirta.writeObject(file3);
			tallennusVirta.flush();
			tallennusVirta.close();
			tallennus.close();	
		}
		catch(FileNotFoundException e){
			System.out.println("Tiedostoa ei löydy.");
		}
		catch(IOException e){
			System.out.println("Virhe Syötteessä.");
		}
		return file3;
	}
}