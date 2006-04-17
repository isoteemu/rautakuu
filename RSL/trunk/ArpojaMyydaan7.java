import java.util.*;
import java.io.*;

public class ArpojaMyydaan
{
	private static String NiMi;
	private static int MaaRa;
	private static double HinTa;
	private static Vector tilasto = new Vector();
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
			System.out.println("Syöte ei saa sisältää muita merkkejä kuin numeroita. Yritä uudelleen.");
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
			System.out.print("\nMyyntierän nimi > ");
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
			NiMi = tiedot.getNimi();
			MaaRa = tiedot.getMaara();
			HinTa = tiedot.getHinta();
			System.out.println(uPtallennus.getFile1() + " ... valmis");
			System.out.println(uPtallennus.getFile2() + " ... valmis");
			System.out.println(uPtallennus.getFile3() + " ... valmis");
		}
		catch(IOException e)
		{
			System.out.println("Virhe syötteessä. Yritä uudelleen.");
			uusiProjekti();
		}
		myyntiValikko();
	}
// 	Wanha myyntierä
	public static void wanhaProjekti(){
		try{
		BufferedReader syote = new BufferedReader(new InputStreamReader(System.in));
		System.out.print("\nSyötä vanhan myyntierän nimi > ");
		String wanhaNimi = syote.readLine();
		projektiAvaus wanha = new projektiAvaus(wanhaNimi);
                int muunto1 = Integer.parseInt(wanha.getMaaraAvaus());
                double muunto2 = Double.parseDouble(wanha.getHintaAvaus());
                projekti wanha2 = new projekti(wanha.getNimiAvaus(), muunto1, muunto2);
		NiMi = wanha2.getNimi();
		MaaRa = wanha2.getMaara();
		HinTa = wanha2.getHinta();
		tilastoAvaus wanhaTilasto = new tilastoAvaus(NiMi);
		tilasto = wanhaTilasto.getFilename();
		}
		catch(IOException e)
		{
			System.out.println("Virhe syötteessä. Yritä uudelleen.");
			wanhaProjekti();
		}
		myyntiValikko();
		}
	
	public static void myyntiValikko(){
		System.out.println("\nProjekti: " + NiMi);
		System.out.println("Arpoja jäljellä: " + MaaRa);
		System.out.println("Yksi arpa maksaa: " + HinTa + "\n");
		System.out.println("1. Myy arpoja\n" +
				  "2. Myyntitilastot\n" +
				  "0. Lopeta\n");
                try{
                BufferedReader syote = new BufferedReader(new InputStreamReader(System.in));
		System.out.print("Valitasi > ");
                String valinta2 = syote.readLine();
                int valinta = Integer.parseInt(valinta2);
		
		switch(valinta){
			case 1:
				syote = new BufferedReader(new InputStreamReader(System.in));
				System.out.print("Ostajan nimi > ");
				valinta2 = syote.readLine();
				System.out.print("Myytävät arvat > ");
				String ostettavaMaara2 = syote.readLine();
				int ostettavaMaara = Integer.parseInt(ostettavaMaara2);
				if(ostettavaMaara > MaaRa){
					System.out.println("Virhe! Arpoja on jäljellä " + MaaRa + " kappaletta.");
					myyntiValikko();
				}
				else{
				double yhteishinta = ostettavaMaara * HinTa;
				MaaRa = MaaRa - ostettavaMaara;
				String log = "\n" + valinta2 + " osti " + ostettavaMaara + " arpaa. Arpojen yhteishinnaksi tuli " + yhteishinta;
				tilasto.add(log);
				System.out.println("\n" + log + "\n");
				}
				myyntiValikko();
			case 2:
				for(int i = 0; i < tilasto.size(); i++){
					String log2 = (String)tilasto.elementAt(i);
					System.out.println(log2);
				}
				myyntiValikko();
			case 0:
					String filename = NiMi + "Tilasto.dat";
					try{
						FileOutputStream tallennus = new FileOutputStream(filename);
						ObjectOutputStream tallennusVirta = new ObjectOutputStream(tallennus);
						tallennusVirta.writeObject(tilasto);
						tallennusVirta.flush();
						tallennusVirta.close();
						tallennus.close();
						System.out.println("Tallennettu...");
					}
					catch(FileNotFoundException e){
						System.out.println("Tiedostoa ei löydy.");
					}
					catch(IOException e){
						System.out.println("Virhe Syötteessä.");
					}
				System.exit(0);
		}
		}
                catch(IOException e){
                           System.out.println("Virhe syötteessä. Yritä uudelleen.");
                           myyntiValikko();
                }
                catch(NumberFormatException e){
                           System.out.println("Syöte ei saa sisältää muita merkkejä kuin numeroita.");
                           myyntiValikko();
                }		
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

class projektiAvaus{
	private String nimi;
	private String nimiMaara;
	private String nimiHinta;
	private String nimiAvaus;
	private String maaraAvaus;
	private String hintaAvaus;
	FileInputStream syottovirta;
	ObjectInputStream lukupuskuri;
	
	public projektiAvaus(String nimiOn){
		nimi = nimiOn + ".dat";
		nimiMaara = nimiOn + "Maara.dat";
		nimiHinta = nimiOn + "Hinta.dat";
		
	}
	
	public void setNimiAvaus(String nimiOn){
		nimi = nimiOn + ".dat";
	}
	public String getNimiAvaus(){
		try{
			syottovirta = new FileInputStream(new File(nimi));
			lukupuskuri = new ObjectInputStream(syottovirta);
			nimiAvaus = (String) lukupuskuri.readObject();
			lukupuskuri.close();
			syottovirta.close();
		}
                catch(ClassNotFoundException e){
                    System.out.println("virhe");
                }
		catch(FileNotFoundException e){
			System.out.println("Tiedostoa ei löydy.");
		}
		catch(IOException e){
			System.out.println("Virhe syötteessä.");
		}
		return nimiAvaus;
	}
	
	public void setMaaraAvaus(String nimiOn){
		nimiMaara = nimiOn + "Maara.dat";
	}
	public String getMaaraAvaus(){
		try{
			syottovirta = new FileInputStream(new File(nimiMaara));
			lukupuskuri = new ObjectInputStream(syottovirta);
			maaraAvaus = (String) lukupuskuri.readObject();
			lukupuskuri.close();
			syottovirta.close();
		}
                catch(ClassNotFoundException e){
                    System.out.println("virhe");
                }    
		catch(FileNotFoundException e){
			System.out.println("Tiedostoa ei löydy.");
		}
		catch(IOException e){
			System.out.println("Virhe syötteessä.");
		}
		return maaraAvaus;
	}
	
	public void setHintaAvaus(String nimiOn){
		nimiHinta = nimiOn + "Hinta.dat";
	}
	public String getHintaAvaus(){
		try{
			syottovirta = new FileInputStream(new File(nimiHinta));
			lukupuskuri = new ObjectInputStream(syottovirta);
			hintaAvaus = (String) lukupuskuri.readObject();
			lukupuskuri.close();
			syottovirta.close();
			
		}
                 catch(ClassNotFoundException e){
                    System.out.println("virhe");
                }
		catch(FileNotFoundException e){
			System.out.println("Tiedostoa ei löydy.");
		}
		catch(IOException e){
			System.out.println("Virhe syötteessä.");
		}
		return hintaAvaus;
	}
}

class tilastoAvaus{
	private String filename;
	FileInputStream tiedostovirta;
	ObjectInputStream objektivirta;
	Vector tilastoo = new Vector();
	
	public tilastoAvaus(String fileIs){
		filename = fileIs + "Tilasto.dat";
	}
	public void setFilename(String fileIs){
		filename = fileIs + "Tilasto.dat";
	}
	public Vector getFilename(){
		try{
			 FileInputStream tilastonAvaus = new FileInputStream(new File(filename));
				ObjectInputStream tilastonAvaus2 = new ObjectInputStream(tilastonAvaus);
				tilastoo = (Vector) tilastonAvaus2.readObject();
				tilastonAvaus.close();
				tilastonAvaus2.close();
		 }
		 catch(FileNotFoundException e){
				System.out.println("Myyntierän tilastoja ei löytynyt. Tämä tarkoittaa sitä," +
						"ettei myyntierästä ole myyty arpoja aikaisempina kertoina tai sitten tilastot sisältänyt tiedosto on tuhottu.");
			}
			catch(IOException e)
			{
				System.out.println("Virhe syötteessä. Yritä uudelleen.");
			}
			 catch(ClassNotFoundException e){
	            System.out.println("virhe");
	        }
			 return tilastoo;
	}

}
