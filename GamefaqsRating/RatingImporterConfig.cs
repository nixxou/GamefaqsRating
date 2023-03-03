using Newtonsoft.Json.Linq;
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using Unbroken.LaunchBox.Plugins;

namespace GamefaqsRating
{
	public partial class RatingImporterConfig : Form
	{
		private static string _pluginPath = "";


		public RatingImporterConfig()
		{
			InitializeComponent();
		}

		public static string GetPluginPath()
		{
			if (_pluginPath != "") return _pluginPath;
			string assemblyPath = Assembly.GetEntryAssembly().Location;
			string assemblyDirectory = Path.GetDirectoryName(assemblyPath);

			string launchBoxRootPath = Path.GetFullPath(Path.Combine(assemblyDirectory, @".."));
			string relativePluginPath = @"Plugins\GamefaqsRating";
			_pluginPath = Path.Combine(launchBoxRootPath, relativePluginPath);
			return _pluginPath;
		}

		private void button1_Click(object sender, EventArgs e)
		{
			var listeJson = Directory.GetFiles(Path.Combine(GetPluginPath(), "gamefaqsdata"), "*.json");

			string zzzs = "1";
			return;



			string GameFaqsJsonFile = Path.Combine(GetPluginPath(), "gamefaqsdata", "Super Nintendo Entertainment System.json");
			if (!File.Exists(GameFaqsJsonFile))
			{
				MessageBox.Show("Cant find data");
				return;
			}

			JObject gameFaqsJson = JObject.Parse(File.ReadAllText(GameFaqsJsonFile));

			var x = PluginHelper.DataManager.GetAllPlatforms();
			foreach(var platform in x)
			{
				

				if(platform.Name == "Super Nintendo Entertainment System" || platform.ScrapeAs == "Super Nintendo Entertainment System")
				{
					Debug.WriteLine(platform.Name);

					foreach(var game in platform.GetAllGames(true,true))
					{
						Debug.WriteLine("Name => " + game.Title);

						var customFields = game.GetAllCustomFields();
						foreach (var field in customFields)
						{
							if (field.Name == "GameFaqs_URL" || field.Name == "GameFaqs_RATING" || field.Name == "GameFaqs_DIFFICULTY" || field.Name == "GameFaqs_LENGHT")
							{
								game.TryRemoveCustomField(field);
							}
						}

						if (gameFaqsJson.ContainsKey(game.LaunchBoxDbId.ToString()))
						{
							var newField = game.AddNewCustomField();
							newField.Name = "GameFaqs_URL";
							newField.Value = gameFaqsJson[game.LaunchBoxDbId.ToString()]["url"].ToString();

							newField = game.AddNewCustomField();
							newField.Name = "GameFaqs_RATING";
							newField.Value = gameFaqsJson[game.LaunchBoxDbId.ToString()]["rating"].ToString();

							newField = game.AddNewCustomField();
							newField.Name = "GameFaqs_DIFFICULTY";
							newField.Value = gameFaqsJson[game.LaunchBoxDbId.ToString()]["difficulty"].ToString();

							newField = game.AddNewCustomField();
							newField.Name = "GameFaqs_LENGHT";
							newField.Value = gameFaqsJson[game.LaunchBoxDbId.ToString()]["lenght"].ToString();

							if(checkBox1.Checked) game.StarRatingFloat = float.Parse(gameFaqsJson[game.LaunchBoxDbId.ToString()]["rating"].ToString());



							Debug.WriteLine("Add url for " + game.Title + " : " + game.LaunchBoxDbId);



						}
					}
				}

				

			}
			PluginHelper.DataManager.Save();
		}

		private void button2_Click(object sender, EventArgs e)
		{
			var plateformList = PluginHelper.DataManager.GetAllPlatforms();
			var listeJson = Directory.GetFiles(Path.Combine(GetPluginPath(), "gamefaqsdata"), "*.json");
			foreach(var json in listeJson)
			{
				string jsonFileNameWithoutExt = Path.GetFileNameWithoutExtension(json);
				foreach (var platform in plateformList)
				{

					JObject gameFaqsJson = null;

					if (platform.Name == jsonFileNameWithoutExt || platform.ScrapeAs == jsonFileNameWithoutExt)
					{
						if (gameFaqsJson == null) gameFaqsJson = JObject.Parse(File.ReadAllText(json));

						foreach (var game in platform.GetAllGames(true, true))
						{
							Debug.WriteLine("Name => " + game.Title);

							var customFields = game.GetAllCustomFields();
							foreach (var field in customFields)
							{
								if (field.Name == "GameFaqs_URL" || field.Name == "GameFaqs_RATING" || field.Name == "GameFaqs_DIFFICULTY" || field.Name == "GameFaqs_LENGHT" || field.Name == "GameFaqs_VOTECOUNT")
								{
									game.TryRemoveCustomField(field);
								}
							}


							if (gameFaqsJson.ContainsKey(game.LaunchBoxDbId.ToString()))
							{
								string url = gameFaqsJson[game.LaunchBoxDbId.ToString()]["url"].ToString();
								string rating = gameFaqsJson[game.LaunchBoxDbId.ToString()]["rating"].ToString();
								string difficulty = gameFaqsJson[game.LaunchBoxDbId.ToString()]["difficulty"].ToString();
								string lenght = gameFaqsJson[game.LaunchBoxDbId.ToString()]["lenght"].ToString();
								string vote = gameFaqsJson[game.LaunchBoxDbId.ToString()]["vote"].ToString();
								int numVote = int.Parse(vote);
								int requiredVote = int.Parse(comboBox1.Text);


								var newField = game.AddNewCustomField();
								newField.Name = "GameFaqs_URL";
								newField.Value = url;

								newField = game.AddNewCustomField();
								newField.Name = "GameFaqs_RATING";
								newField.Value = rating;

								newField = game.AddNewCustomField();
								newField.Name = "GameFaqs_DIFFICULTY";
								newField.Value = difficulty;

								newField = game.AddNewCustomField();
								newField.Name = "GameFaqs_LENGHT";
								newField.Value = lenght;

								newField = game.AddNewCustomField();
								newField.Name = "GameFaqs_VOTECOUNT";
								newField.Value = vote;

								float ratingFloat;
								if (Single.TryParse(rating, out ratingFloat))
								{

									if (checkBox1.Checked) game.StarRatingFloat = 0;
									if (checkBox1.Checked && numVote >= requiredVote)
									{
										game.StarRatingFloat = float.Parse(gameFaqsJson[game.LaunchBoxDbId.ToString()]["rating"].ToString());
									}
								}






								Debug.WriteLine("Add url for " + game.Title + " : " + game.LaunchBoxDbId);

							}



						}



					}
				}

			}
			PluginHelper.DataManager.Save();
			MessageBox.Show("Done !");

		}

		private void RatingImporterConfig_Load(object sender, EventArgs e)
		{
			comboBox1.SelectedIndex = 2;
		}
	}
}
